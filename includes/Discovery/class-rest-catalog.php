<?php
/**
 * Public read-only catalog search REST endpoint for AI agents.
 *
 * GET /wp-json/acpfw/v1/ucp/catalog/search
 *
 * Query params: q (search text), page (1+), per_page (1-50, default 20).
 * Read-only, unauthenticated, and write-protected by design: it exposes the
 * same publishable catalog rows the feeds export, mapped to the same
 * wc_{id} item identifiers, so a UCP agent can query live inventory without
 * downloading the whole JSONL first. "Same rows" is enforced literally:
 * grouped containers, unpriced products and external/affiliate items are
 * filtered out exactly like the feed generator filters them, and variable
 * listings are priced from their cheapest variation, never "0.00".
 *
 * @package ACPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and serves the UCP catalog search route.
 */
final class ACPFW_Rest_Catalog {

        /**
         * Maximum items per page.
         *
         * @var int
         */
        const MAX_PER_PAGE = 50;

        /**
         * Wire up REST registration.
         *
         * @return void
         */
        public static function init() {
                add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
        }

        /**
         * Register the search route.
         *
         * @return void
         */
        public static function register_routes() {
                register_rest_route(
                        'acpfw/v1',
                        '/ucp/catalog/search',
                        array(
                                'methods'             => WP_REST_Server::READABLE,
                                'callback'            => array( __CLASS__, 'handle_search' ),
                                'permission_callback' => '__return_true', // Public read-only catalog data, identical to the public feed files.
                                'args'                => array(
                                        'q'        => array(
                                                'type'              => 'string',
                                                'required'          => false,
                                                'sanitize_callback' => 'sanitize_text_field',
                                        ),
                                        'page'     => array(
                                                'type'              => 'integer',
                                                'required'          => false,
                                                'sanitize_callback' => 'absint',
                                        ),
                                        'per_page' => array(
                                                'type'              => 'integer',
                                                'required'          => false,
                                                'sanitize_callback' => 'absint',
                                        ),
                                ),
                        )
                );
        }

        /**
         * Serve one page of catalog results.
         *
         * @param WP_REST_Request $request Request object.
         * @return WP_REST_Response
         */
        public static function handle_search( $request ) {
                $q        = trim( (string) ( $request->get_param( 'q' ) !== null ? $request->get_param( 'q' ) : '' ) );
                $page     = max( 1, (int) $request->get_param( 'page' ) );
                $per_page = (int) $request->get_param( 'per_page' );

                if ( $per_page <= 0 ) {
                        $per_page = 20;
                }

                $per_page = min( self::MAX_PER_PAGE, $per_page );

                /*
                 * Cheap abuse guard for an unauthenticated endpoint: a small
                 * per-IP budget (transient counter, 60 searches / 10 minutes).
                 * Legitimate agents page through far less than that.
                 */
                $throttle = self::throttle_check();

                if ( is_wp_error( $throttle ) ) {
                        return $throttle;
                }

                /*
                 * Response cache: identical browsing hits within 5 minutes are
                 * served from a transient, so crawls never re-run WC queries
                 * per hit. Only empty-query browsing (pages 1-10) is cached:
                 * unique q strings are unbounded, and every transient is an
                 * options-table row, so caching arbitrary queries would let
                 * distributed callers bloat the database. The per-IP throttle
                 * covers the uncached search path.
                 */
                $cacheable = ( '' === $q && $page <= 10 );
                /* The v2 prefix also orphans stale cached payloads from the
                 * previous row logic (which carried "0.00 USD" prices), so an
                 * updated site never serves them for up to five minutes. */
                $cache_key = 'acpfw_search_v2_' . md5( $q . '|' . $page . '|' . $per_page );

                if ( $cacheable ) {
                        $cached = get_transient( $cache_key );

                        if ( is_array( $cached ) ) {
                                return rest_ensure_response( $cached );
                        }
                }

                // Fetch one extra row so we can report has_more without a count query.
                $args = array(
                        'status'   => 'publish',
                        'limit'    => $per_page + 1,
                        'page'     => $page,
                        'orderby'  => '' !== $q ? 'relevance' : 'title',
                        'order'    => 'ASC',
                        'return'   => 'objects',
                );

                if ( '' !== $q ) {
                        $args['search'] = $q;
                }

                $products = wc_get_products( $args );

                if ( ! is_array( $products ) ) {
                        $products = array();
                }

                $has_more = count( $products ) > $per_page;

                /*
                 * has_more describes the underlying pre-filter catalog window:
                 * a page can carry fewer than per_page items when products
                 * were filtered out of the window (visibility, opt-out,
                 * grouped/unpriced/external, zero purchasable variants),
                 * while later pages still hold public products. Every
                 * exported product appears on exactly one page.
                 */

                if ( $has_more ) {
                        $products = array_slice( $products, 0, $per_page );
                }

                $items = array();

                foreach ( $products as $product ) {
                        if ( ! $product instanceof WC_Product ) {
                                continue;
                        }

                        // Same visibility rules the feeds honor: hidden,
                        // search-only and password-protected products stay out.
                        if ( ! ACPFW_Plugin_Core::product_is_ai_public( $product ) ) {
                                continue;
                        }

                        // Same opt-out the feeds honor.
                        if ( 'yes' === get_post_meta( $product->get_id(), '_acpfw_exclude_from_feed', true ) ) {
                                continue;
                        }

                        // Grouped products are containers, not purchasable
                        // rows - the same rule the feeds honor.
                        if ( $product->is_type( 'grouped' ) ) {
                                continue;
                        }

                        $is_variable = $product->is_type( 'variable' );

                        if ( $is_variable ) {
                                /*
                                 * A variable listing is exported only when at
                                 * least one purchasable, non-excluded, unique
                                 * option combination exists - the exact
                                 * condition under which the feeds emit rows
                                 * for this group.
                                 */
                                $counts = ACPFW_Product_Mapper::get_variation_export_counts( $product->get_id() );

                                if ( (int) $counts['exportable'] <= 0 ) {
                                        continue;
                                }

                                /*
                                 * Variable parents carry no price of their own
                                 * (get_regular_price() is empty, which rendered
                                 * as "0.00 USD"), so the listing is priced from
                                 * the cheapest variation's RAW catalog price and
                                 * passed through display_price() exactly once -
                                 * the same tax handling every feed row gets. The
                                 * raw getters, not the for_display variants, are
                                 * used so the tax display adjustment is never
                                 * applied twice.
                                 */
                                $regular_raw = (float) $product->get_variation_regular_price( 'min' );
                                $active_raw  = (float) $product->get_variation_price( 'min' );
                        } elseif ( ! $product->is_purchasable() ) {
                                /*
                                 * Simple and external products follow the feed
                                 * rule: unpriced products (no price set) and
                                 * external/affiliate items (WooCommerce marks
                                 * them never purchasable) stay out, so this
                                 * endpoint lists exactly what the JSONL/CSV
                                 * feeds export.
                                 */
                                continue;
                        } else {
                                $regular_raw = (float) $product->get_regular_price();
                                $active_raw  = $product->is_on_sale() ? (float) $product->get_sale_price() : 0.0;
                        }

                        $item = array(
                                'item_id'      => 'wc_' . $product->get_id(),
                                'title'        => ACPFW_Product_Mapper::format_title( $product ),
                                'url'          => (string) $product->get_permalink(),
                                'price'        => self::format_price( ACPFW_Product_Mapper::display_price( $product, $regular_raw ) ),
                                'availability' => self::availability( $product ),
                                'brand'        => ACPFW_Product_Mapper::get_resolved_brand( $product->get_id() ),
                                'type'         => $product->get_type(),
                        );

                        /* Variable: a sale is reported when the cheapest active
                         * variation price undercuts the cheapest regular price.
                         * Simple: the WooCommerce sale state decides, unchanged. */
                        $on_sale = $is_variable
                                ? ( $active_raw > 0 && $active_raw < $regular_raw )
                                : ( $product->is_on_sale() && $active_raw > 0 );

                        if ( $on_sale ) {
                                $item['sale_price'] = self::format_price( ACPFW_Product_Mapper::display_price( $product, $active_raw ) );
                        }

                        $image_id = (int) $product->get_image_id();

                        if ( $image_id > 0 ) {
                                /* allow_generate = false: a public REST request must
                                 * never be able to write image files to uploads. */
                                $image = ACPFW_Image_Resolver::get_feed_url( $image_id, false );

                                if ( '' !== $image ) {
                                        $item['image_url'] = $image;
                                }
                        }

                        if ( $is_variable ) {
                                $item['group_id']               = 'wc_' . $product->get_id();
                                $item['listing_has_variations'] = true;
                                $item['purchasable_variants']   = (int) $counts['exportable'];
                        }

                        $items[] = $item;
                }

                $response = array(
                        'query'    => $q,
                        'page'     => $page,
                        'per_page' => $per_page,
                        'count'    => count( $items ),
                        'has_more' => $has_more,
                        'items'    => $items,
                        'feeds'    => array(
                                'products_jsonl' => ACPFW_OpenAI_Feed_Generator::get_feed_url( 'jsonl' ),
                                'products_csv'   => ACPFW_OpenAI_Feed_Generator::get_feed_url( 'csv' ),
                        ),
                );

                if ( $cacheable ) {
                        set_transient( $cache_key, $response, 5 * MINUTE_IN_SECONDS );
                }

                return rest_ensure_response( $response );
        }

        /**
         * Per-requester budget for the public search route.
         *
         * @return true|WP_Error True when allowed, WP_Error (429) when exhausted.
         */
        private static function throttle_check() {
                $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitized on use.

                /**
                 * Filter the client IP used by the public search throttle.
                 *
                 * Behind a CDN or reverse proxy every visitor can share one
                 * REMOTE_ADDR, so AI agents would throttle each other. On such
                 * setups return the real client IP from a trusted header (e.g.
                 * CF-Connecting-IP or the last X-Forwarded-For hop you control).
                 * Only return values you can trust - never echo a spoofable
                 * header blindly, or a caller could rotate fake IPs to bypass
                 * the budget.
                 *
                 * @param string $ip Client IP from REMOTE_ADDR ('' when absent).
                 */
                $ip = (string) apply_filters( 'acpfw_client_ip', $ip );

                if ( '' === $ip ) {
                        return true;
                }

                $key   = 'acpfw_search_rl_' . md5( $ip );
                $hits  = (int) get_transient( $key );
                $limit = 60;

                if ( $hits >= $limit ) {
                        return new WP_Error(
                                'acpfw_rate_limited',
                                __( 'Too many catalog searches. Slow down and retry shortly.', 'ai-product-feed-agentic-commerce' ),
                                array( 'status' => 429 )
                        );
                }

                set_transient( $key, $hits + 1, 10 * MINUTE_IN_SECONDS );

                return true;
        }

        /**
         * Feed-style money string, e.g. "79.99 USD".
         *
         * @param float $amount Raw amount.
         * @return string
         */
        private static function format_price( $amount ) {
                return number_format( $amount, 2, '.', '' ) . ' ' . get_woocommerce_currency();
        }

        /**
         * Map WooCommerce stock status to the OpenAI availability enum.
         *
         * @param WC_Product $product Product object.
         * @return string
         */
        private static function availability( $product ) {
                switch ( $product->get_stock_status() ) {
                        case 'instock':
                                return 'in_stock';
                        case 'outofstock':
                                return 'out_of_stock';
                        case 'onbackorder':
                                return 'backorder';
                        default:
                                return 'unknown';
                }
        }
}
