<?php
/**
 * AI Bot Allowlist & Crawler Monitor.
 *
 * Environment health checks for the dashboard:
 *
 *  1. "Discourage search engines" WordPress option (blocks honest crawlers).
 *  2. robots.txt scan: warns when an AI crawler (GPTBot, OAI-SearchBot,
 *     PerplexityBot, ClaudeBot, ...) is fully disallowed — the #1 reason a
 *     store never appears in AI answers despite a perfect feed.
 *  3. Live reachability of /llms.txt and the JSONL feed URL.
 *
 * Results are cached in a transient for 30 minutes; the dashboard offers a
 * "Re-run checks" button.
 *
 * @package ACPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Runs and renders the AI-crawler environment checks.
 */
final class ACPFW_Env_Checks {

        const CACHE_KEY = 'acpfw_env_check';

        /**
         * AI crawlers worth watching, with friendly labels.
         *
         * @return array
         */
        private static function monitored_bots() {
                return array(
                        'GPTBot'             => __( 'OpenAI training crawler', 'ai-product-feed-agentic-commerce' ),
                        'OAI-SearchBot'      => __( 'ChatGPT Search', 'ai-product-feed-agentic-commerce' ),
                        'ChatGPT-User'       => __( 'ChatGPT user browsing', 'ai-product-feed-agentic-commerce' ),
                        'PerplexityBot'      => __( 'Perplexity Shopping', 'ai-product-feed-agentic-commerce' ),
                        'ClaudeBot'          => __( 'Claude', 'ai-product-feed-agentic-commerce' ),
                        'Google-Extended'    => __( 'Gemini / Google AI', 'ai-product-feed-agentic-commerce' ),
                        'CCBot'              => __( 'Common Crawl (feeds many AI models)', 'ai-product-feed-agentic-commerce' ),
                        'Amazonbot'          => __( 'Amazon AI (Rufus)', 'ai-product-feed-agentic-commerce' ),
                );
        }

        /**
         * Constructor.
         */
        public function __construct() {
                add_action( 'admin_post_acpfw_env_recheck', array( $this, 'handle_recheck' ) );
        }

        /**
         * "Re-run checks" button handler.
         *
         * @return void
         */
        public function handle_recheck() {
                if ( ! current_user_can( 'manage_woocommerce' ) ) {
                        wp_die( esc_html__( 'You are not allowed to manage settings.', 'ai-product-feed-agentic-commerce' ) );
                }

                check_admin_referer( 'acpfw_env_recheck' );

                delete_transient( self::CACHE_KEY );
                self::get_results( true );

                wp_safe_redirect(
                        add_query_arg(
                                array(
                                        'page'         => 'acpfw-feed',
                                        'acpfw_notice' => 'env_rechecked',
                                ),
                                admin_url( 'admin.php' )
                        )
                );
                exit;
        }

        /**
         * Run (or fetch cached) check results.
         *
         * @param bool $force Skip the cache.
         * @return array
         */
        public static function get_results( $force = false ) {
                if ( ! $force ) {
                        $cached = get_transient( self::CACHE_KEY );

                        if ( is_array( $cached ) ) {
                                return $cached;
                        }
                }

                $results = array();

                // 0. Public site URL? A dev site on localhost/private IPs can
                // generate perfect feeds that no AI crawler can ever fetch.
                $results['site_url'] = self::check_site_url();

                // 1. Search engines discouraged?
                $blog_public = (string) get_option( 'blog_public', '1' );

                $results['blog_public'] = array(
                        'status' => ( '1' === $blog_public ) ? 'pass' : 'fail',
                        'label'  => __( 'Search engines allowed', 'ai-product-feed-agentic-commerce' ),
                        'detail' => ( '1' === $blog_public )
                                ? __( 'WordPress is not discouraging crawlers.', 'ai-product-feed-agentic-commerce' )
                                : __( 'Settings > Reading has "Discourage search engines" enabled — most AI crawlers will respect this and stay away.', 'ai-product-feed-agentic-commerce' ),
                );

                // 2. robots.txt AI bot scan.
                $results['robots'] = self::check_robots();

                // 3. llms.txt reachable?
                $results['llms'] = self::check_url(
                        ACPFW_LLMS_Txt_Generator::get_llms_url(),
                        __( 'llms.txt reachable', 'ai-product-feed-agentic-commerce' )
                );

                // 4. JSONL feed reachable?
                $feed_check = self::check_url(
                        ACPFW_OpenAI_Feed_Generator::get_feed_url( 'jsonl' ),
                        __( 'Product feed reachable', 'ai-product-feed-agentic-commerce' )
                );

                if ( 'fail' === $feed_check['status'] ) {
                        $feed_check['detail'] = __( 'Feed file not found yet — click "Generate / refresh feeds now".', 'ai-product-feed-agentic-commerce' );
                }

                $results['feed'] = $feed_check;

                // 5. Return policy URL usable? The feeds silently omit
                // placeholder/homepage policy URLs (a wrong policy claim is
                // worse than none), so the dashboard must tell the merchant.
                $results['return_policy'] = self::check_return_policy();

                set_transient( self::CACHE_KEY, $results, 30 * MINUTE_IN_SECONDS );

                return $results;
        }

        /**
         * Site URL sanity: publicly resolvable host + HTTPS scheme.
         *
         * Feeds generated on http://localhost are fine during development,
         * but the merchant must be warned before submitting them anywhere.
         *
         * @return array
         */
        private static function check_site_url() {
                $host   = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
                $scheme = (string) wp_parse_url( home_url(), PHP_URL_SCHEME );

                $private = in_array( $host, array( 'localhost', '127.0.0.1', '::1', '0.0.0.0' ), true )
                        || '.localhost' === substr( $host, -10 )
                        || '.local' === substr( $host, -6 )
                        || (bool) preg_match( '/^192\.168\./', $host )
                        || (bool) preg_match( '/^10\./', $host )
                        || (bool) preg_match( '/^172\.(1[6-9]|2[0-9]|3[01])\./', $host );

                if ( $private ) {
                        return array(
                                'status' => 'fail',
                                'label'  => __( 'Site URL is publicly reachable', 'ai-product-feed-agentic-commerce' ),
                                'detail' => sprintf(
                                        /* translators: %s: current site host, e.g. localhost */
                                        __( 'Your WordPress address points at "%s" — a private/dev host. Every feed row and llms.txt link carries this hostname, so AI crawlers and OpenAI cannot fetch anything. Move the site to a public domain (Settings > General) before submitting the feed.', 'ai-product-feed-agentic-commerce' ),
                                        $host
                                ),
                        );
                }

                if ( 'https' !== strtolower( $scheme ) ) {
                        return array(
                                'status' => 'warn',
                                'label'  => __( 'Site URL uses HTTPS', 'ai-product-feed-agentic-commerce' ),
                                'detail' => __( 'Your site URL starts with http://. Most AI shopping channels expect https:// product and image URLs — install an SSL certificate and update the WordPress address before going live.', 'ai-product-feed-agentic-commerce' ),
                        );
                }

                return array(
                        'status' => 'pass',
                        'label'  => __( 'Site URL is publicly reachable', 'ai-product-feed-agentic-commerce' ),
                        'detail' => __( 'Your WordPress address is a public HTTPS hostname — feeds and discovery URLs are fetchable from the internet.', 'ai-product-feed-agentic-commerce' ),
                );
        }

        /**
         * Return policy URL: present and not a placeholder.
         *
         * AI shopping channels expect the URL of the actual returns page; the
         * feed mapper silently drops homepage/placeholder values so no false
         * policy claim is ever exported. This check explains that behavior in
         * the dashboard and nudges merchants to set a real policy URL.
         *
         * @return array
         */
        private static function check_return_policy() {
                $settings = function_exists( 'acpfw' ) ? acpfw()->get_settings() : array();
                $url      = isset( $settings['return_policy_url'] ) ? trim( (string) $settings['return_policy_url'] ) : '';

                if ( '' === $url ) {
                        return array(
                                'status' => 'warn',
                                'label'  => __( 'Return policy URL set', 'ai-product-feed-agentic-commerce' ),
                                'detail' => __( 'No return policy URL is configured, so feed rows ship without a return_policy field. Add the full URL of your returns/refunds page in the plugin settings.', 'ai-product-feed-agentic-commerce' ),
                        );
                }

                if ( ACPFW_Product_Mapper::is_placeholder_policy_url( $url ) ) {
                        return array(
                                'status' => 'warn',
                                'label'  => __( 'Return policy URL set', 'ai-product-feed-agentic-commerce' ),
                                'detail' => __( 'The configured return policy URL points at your store home page. AI shopping channels expect the actual returns/refunds page — point the setting at that dedicated page so the URL is exported.', 'ai-product-feed-agentic-commerce' ),
                        );
                }

                return array(
                        'status' => 'pass',
                        'label'  => __( 'Return policy URL set', 'ai-product-feed-agentic-commerce' ),
                        'detail' => __( 'A dedicated return policy URL is configured and is exported with every feed row.', 'ai-product-feed-agentic-commerce' ),
                );
        }

        /**
         * Scan the effective robots.txt output for AI bot blocks.
         *
         * @return array
         */
        private static function check_robots() {
                $public = '1' === (string) get_option( 'blog_public', '1' );

                $base   = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n";
                // Re-fires WordPress core's own "robots_txt" filter on a synthetic base to
                // compute the effective robots.txt output; the hook name belongs to WordPress.
                $robots = $public ? (string) apply_filters( 'robots_txt', $base, true ) : ''; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core hook name, deliberately re-fired.
                $rows   = array();
                $status = 'pass';

                foreach ( self::monitored_bots() as $agent => $label ) {
                        $blocked = self::bot_is_blocked( $robots, $agent );
                        $rows[]  = array(
                                'agent'   => $agent,
                                'label'   => $label,
                                'blocked' => $blocked,
                        );

                        if ( $blocked ) {
                                $status = 'fail';
                        }
                }

                return array(
                        'status' => $status,
                        'label'  => __( 'AI crawlers in robots.txt', 'ai-product-feed-agentic-commerce' ),
                        'detail' => ( 'fail' === $status )
                                ? __( 'One or more AI crawlers cannot fetch your discovery URLs per robots.txt rules (set by a plugin, your host, or Cloudflare). Unblock them or AI assistants cannot read your products.', 'ai-product-feed-agentic-commerce' )
                                : __( 'No AI crawler is blocked by WordPress robots.txt filters. (Hosting firewalls and CDNs are outside WordPress and not covered here.)', 'ai-product-feed-agentic-commerce' ),
                        'rows'   => $rows,
                );
        }

        /**
         * Does robots.txt stop this user agent from fetching our discovery URLs?
         *
         * Implements the robots exclusion semantics that matter for the
         * "can this bot fetch /llms.txt" decision:
         *  - consecutive User-agent lines share one group (Google's parser);
         *  - a bot-specific group replaces the "*" group for that bot;
         *  - an empty Disallow value means "allow everything" (NOT a block);
         *  - longest match wins, ties go to Allow, and "*" wildcards in
         *    paths (common in SEO/security plugin output) are honored.
         *
         * @param string $robots Effective robots.txt text.
         * @param string $agent  User agent token.
         * @return bool True when any probed URL is disallowed for the bot.
         */
        private static function bot_is_blocked( $robots, $agent ) {
                if ( '' === $robots ) {
                        return false;
                }

                $groups = self::parse_robots_groups( $robots );

                if ( empty( $groups ) ) {
                        return false;
                }

                $bot_rules     = array();
                $star_rules    = array();
                $has_bot_group = false;

                foreach ( $groups as $group ) {
                        $match_star = false;
                        $match_bot  = false;

                        foreach ( $group['agents'] as $ua ) {
                                if ( '*' === $ua ) {
                                        $match_star = true;
                                } elseif ( 0 === strcasecmp( $ua, $agent ) ) {
                                        $match_bot = true;
                                }
                        }

                        /* A bot-named group exists even when it is empty: it then
                         * REPLACES the "*" group for this bot (empty Disallow =
                         * allow everything). */
                        if ( $match_bot ) {
                                $has_bot_group = true;
                                $bot_rules     = array_merge( $bot_rules, $group['rules'] );
                        } elseif ( $match_star ) {
                                $star_rules = array_merge( $star_rules, $group['rules'] );
                        }
                }

                $rules = $has_bot_group ? $bot_rules : $star_rules;

                if ( empty( $rules ) ) {
                        return false;
                }

                foreach ( array( '/', '/llms.txt', '/llms-full.txt' ) as $probe ) {
                        if ( ! self::robots_path_allowed( $rules, $probe ) ) {
                                return true;
                        }
                }

                return false;
        }

        /**
         * Longest-match evaluation for one URL path against collected rules.
         *
         * @param array  $rules array{type:string,path:string}[].
         * @param string $path  URL path to test.
         * @return bool True when the path may be fetched.
         */
        private static function robots_path_allowed( $rules, $path ) {
                $best_len = -1;
                $allowed  = true;

                foreach ( $rules as $rule ) {
                        if ( ! self::robots_rule_matches( $rule['path'], $path ) ) {
                                continue;
                        }

                        $len = strlen( $rule['path'] );

                        if ( $len > $best_len ) {
                                $best_len = $len;
                                $allowed  = ( 'allow' === $rule['type'] );
                        } elseif ( $len === $best_len && 'allow' === $rule['type'] ) {
                                $allowed = true; // Ties go to the more permissive rule.
                        }
                }

                return $allowed;
        }

        /**
         * Prefix match with "*" wildcard support.
         *
         * @param string $rule_path Rule path from robots.txt.
         * @param string $path      URL path to test.
         * @return bool
         */
        private static function robots_rule_matches( $rule_path, $path ) {
                if ( '' === $rule_path ) {
                        return false;
                }

                if ( false !== strpos( $rule_path, '*' ) ) {
                        $regex = '#^' . str_replace( '\*', '.*', preg_quote( $rule_path, '#' ) ) . '#';

                        return (bool) preg_match( $regex, $path );
                }

                return 0 === strpos( $path, $rule_path );
        }

        /**
         * Parse a robots.txt body into agent groups with allow/disallow rules.
         *
         * @param string $robots robots.txt text.
         * @return array[] Each: agents => string[], rules => array{type:string,path:string}[].
         */
        private static function parse_robots_groups( $robots ) {
                $lines   = preg_split( '/\R/', (string) $robots );
                $groups  = array();
                $current = null;
                $saw_rule = false;

                foreach ( (array) $lines as $line ) {
                        $line = trim( preg_replace( '/#.*$/', '', (string) $line ) );

                        if ( '' === $line ) {
                                continue;
                        }

                        $parts = explode( ':', $line, 2 );

                        if ( count( $parts ) < 2 ) {
                                continue;
                        }

                        $field = strtolower( trim( $parts[0] ) );
                        $value = trim( $parts[1] );

                        if ( 'user-agent' === $field ) {
                                /* Consecutive User-agent lines share one group; a
                                 * User-agent line after a rule opens a new group. */
                                if ( null === $current || $saw_rule ) {
                                        if ( null !== $current ) {
                                                $groups[] = $current;
                                        }

                                        $current  = array( 'agents' => array(), 'rules' => array() );
                                        $saw_rule = false;
                                }

                                $current['agents'][] = $value;

                                continue;
                        }

                        if ( null === $current ) {
                                continue; // Rules before any User-agent are ignored.
                        }

                        if ( 'disallow' === $field ) {
                                /* Empty value = "allow everything": explicitly not a
                                 * blocking rule, so it is skipped. */
                                if ( '' !== $value ) {
                                        $current['rules'][] = array( 'type' => 'disallow', 'path' => $value );
                                }

                                $saw_rule = true;

                                continue;
                        }

                        if ( 'allow' === $field ) {
                                if ( '' !== $value ) {
                                        $current['rules'][] = array( 'type' => 'allow', 'path' => $value );
                                }

                                $saw_rule = true;

                                continue;
                        }

                        // Sitemap / Crawl-delay / unknown fields still close a group.
                        $saw_rule = true;
                }

                if ( null !== $current ) {
                        $groups[] = $current;
                }

                return $groups;
        }

        /**
         * Reachability check for a public URL on this very site.
         *
         * @param string $url  URL to probe.
         * @param string $label Friendly label.
         * @return array
         */
        private static function check_url( $url, $label ) {
                $response = wp_remote_head( $url, array( 'timeout' => 10 ) );

                if ( is_wp_error( $response ) ) {
                        // Some hosts 405 HEAD; retry with GET before giving up.
                        $response = wp_remote_get( $url, array( 'timeout' => 10 ) );
                }

                if ( is_wp_error( $response ) ) {
                        return array(
                                'status' => 'warn',
                                'label'  => $label,
                                'detail' => __( 'Could not reach the URL from inside WordPress (loopback requests may be blocked by your host — common on local dev). Visit the URL manually to confirm.', 'ai-product-feed-agentic-commerce' ),
                        );
                }

                $code = (int) wp_remote_retrieve_response_code( $response );

                return array(
                        'status' => ( $code >= 200 && $code < 300 ) ? 'pass' : 'fail',
                        'label'  => $label,
                        'detail' => ( $code >= 200 && $code < 300 )
                                ? sprintf( /* translators: %d: HTTP status code */ __( 'HTTP %d — serving correctly.', 'ai-product-feed-agentic-commerce' ), $code )
                                : sprintf( /* translators: %d: HTTP status code */ __( 'HTTP %d — see the fix hints on this page. If permalinks were just enabled, re-save Settings > Permalinks.', 'ai-product-feed-agentic-commerce' ), $code ),
                );
        }

        /**
         * Render the checks panel (inside the dashboard discovery section).
         *
         * @return void
         */
        public static function render() {
                $results = self::get_results();
                ?>
                <h3 class="acpfw-monitor__heading"><?php esc_html_e( 'AI crawler monitor', 'ai-product-feed-agentic-commerce' ); ?></h3>

                <ul class="acpfw-monitor">
                        <?php foreach ( $results as $result ) : ?>
                                <?php
                                $mod = ( 'pass' === $result['status'] ) ? 'pass' : ( ( 'warn' === $result['status'] ) ? 'warn' : 'fail' );
                                $icon = ( 'pass' === $result['status'] ) ? '✓' : ( ( 'warn' === $result['status'] ) ? '!' : '✕' );
                                ?>
                                <li>
                                        <span class="acpfw-monitor__icon acpfw-monitor__icon--<?php echo esc_attr( $mod ); ?>"><?php echo esc_html( $icon ); ?></span>
                                        <span>
                                                <span class="acpfw-monitor__label"><?php echo esc_html( $result['label'] ); ?></span>
                                                <span class="acpfw-monitor__detail acpfw-monitor__detail--block"><?php echo esc_html( $result['detail'] ); ?></span>
                                        </span>
                                </li>

                                <?php if ( ! empty( $result['rows'] ) ) : ?>
                                        <?php foreach ( $result['rows'] as $bot ) : ?>
                                                <?php $bot_mod = $bot['blocked'] ? 'fail' : 'pass'; ?>
                                                <li class="acpfw-monitor__subrow">
                                                        <span class="acpfw-monitor__icon acpfw-monitor__icon--<?php echo esc_attr( $bot_mod ); ?> acpfw-monitor__icon--mini"><?php echo $bot['blocked'] ? '✕' : '✓'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static glyphs. ?></span>
                                                        <span class="acpfw-monitor__botlabel">
                                                                <code><?php echo esc_html( $bot['agent'] ); ?></code> — <?php echo esc_html( $bot['label'] ); ?>
                                                                <?php if ( $bot['blocked'] ) : ?>
                                                                        <strong class="acpfw-monitor__blocked"><?php esc_html_e( 'BLOCKED', 'ai-product-feed-agentic-commerce' ); ?></strong>
                                                                <?php endif; ?>
                                                        </span>
                                                </li>
                                        <?php endforeach; ?>
                                <?php endif; ?>
                        <?php endforeach; ?>
                </ul>

                <p class="acpfw-monitor__actions">
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="acpfw-inline-form">
                                <input type="hidden" name="action" value="acpfw_env_recheck" />
                                <?php wp_nonce_field( 'acpfw_env_recheck' ); ?>
                                <button type="submit" class="button acpfw-btn">
                                        <span class="dashicons dashicons-update-alt"></span>
                                        <?php esc_html_e( 'Re-run checks', 'ai-product-feed-agentic-commerce' ); ?>
                                </button>
                        </form>
                </p>
                <?php
        }
}
