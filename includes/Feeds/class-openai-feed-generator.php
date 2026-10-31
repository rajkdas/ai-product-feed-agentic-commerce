<?php
/**
 * OpenAI feed generator: builds the .jsonl and .csv feed files locally.
 *
 * - Storage: wp-content/uploads/acpfw-feeds/openai-feed-{16-char-token}.{ext}
 *   Random-token capability URLs; directory listing blocked by an index.php stub.
 * - Atomic writes: rows stream into .tmp files which are rename()d into place,
 *   so an AI crawler never reads a half-written feed.
 * - Memory safe: products are pulled in pages of 100 and rows stream straight
 *   to disk; nothing accumulates in memory.
 *
 * @package ACPFW
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generates and manages the hosted OpenAI feed files.
 */
final class ACPFW_OpenAI_Feed_Generator {

        const PAGE_SIZE = 100;

        /**
         * Stable secret token used in feed file names.
         *
         * @return string
         */
        public static function get_feed_token() {
                $token = (string) get_option( 'acpfw_feed_token', '' );

                if ( '' === $token ) {
                        $token = wp_generate_password( 16, false, false );
                        update_option( 'acpfw_feed_token', $token, false );
                }

                return $token;
        }

        /**
         * File name for a feed type.
         *
         * @param string $type 'jsonl' or 'csv'.
         * @return string
         */
        public static function get_feed_filename( $type ) {
                return 'openai-feed-' . self::get_feed_token() . '.' . sanitize_key( $type );
        }

        /**
         * Public URL for a feed file.
         *
         * @param string $type 'jsonl' or 'csv'.
         * @return string
         */
        public static function get_feed_url( $type ) {
                return ACPFW_Plugin_Core::get_feed_directory_url() . self::get_feed_filename( $type );
        }

        /**
         * Timestamp + counters of the last successful generation.
         *
         * @return array
         */
        public static function get_stats() {
                $stats = get_option( 'acpfw_feed_stats', array() );

                return is_array( $stats ) ? $stats : array();
        }

        /**
         * Generate both feed files from the full published catalog.
         *
         * @return array Stats: rows, products, generated_at.
         */
        public static function generate() {
                /*
                 * Concurrency lock: the Action Scheduler job, the debounced
                 * product-change rebuild, the external cron URL and the manual
                 * dashboard button all funnel through this method. Only one full
                 * build may run at a time; overlapping callers exit immediately.
                 * The TTL also self-heals after a fatal (no unlock runs).
                 */
                if ( get_transient( 'acpfw_generate_lock' ) ) {
                        return array(
                                'rows'         => 0,
                                'products'     => 0,
                                'generated_at' => 0,
                                'error'        => 'already_running',
                        );
                }

                set_transient( 'acpfw_generate_lock', time(), 10 * MINUTE_IN_SECONDS );

                /*
                 * Machine-readable output must not vary with the locale of whoever
                 * (or whatever request) triggers the build: always generate in the
                 * site's default locale (Settings > General).
                 */
                $switched_locale = false;

                if ( function_exists( 'switch_to_locale' ) ) {
                        $switched_locale = switch_to_locale( self::site_locale_safe() );
                }

                $stats = self::generate_locked();

                if ( $switched_locale && function_exists( 'restore_current_locale' ) ) {
                        restore_current_locale();
                }

                return $stats;
        }

        /**
         * Site locale with a guard for very early calls before the core API
         * is fully available (uninstall/cron edge cases).
         *
         * @return string
         */
        private static function site_locale_safe() {
                if ( class_exists( 'ACPFW_Plugin_Core' ) && method_exists( 'ACPFW_Plugin_Core', 'site_locale' ) ) {
                        return ACPFW_Plugin_Core::site_locale();
                }

                $locale = (string) get_option( 'WPLANG', '' );

                return '' !== $locale ? $locale : 'en_US';
        }

        /**
         * The actual build; always called under the concurrency lock.
         *
         * @return array Stats: rows, products, generated_at.
         */
        private static function generate_locked() {
                $feed_dir   = ACPFW_Plugin_Core::prepare_feed_directory();
                $jsonl_path = trailingslashit( $feed_dir ) . self::get_feed_filename( 'jsonl' );
                $csv_path   = trailingslashit( $feed_dir ) . self::get_feed_filename( 'csv' );

                $tmp_jsonl = $jsonl_path . '.tmp-' . uniqid( '', true );
                $tmp_csv   = $csv_path . '.tmp-' . uniqid( '', true );

                $jsonl_handle = fopen( $tmp_jsonl, 'wb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- atomic temp-file streaming.
                $csv_handle   = fopen( $tmp_csv, 'wb' );   // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Streaming write to a temp file: WP_Filesystem has no append API and buffering arbitrarily large catalogs in memory can exceed PHP memory_limit.

                if ( ! $jsonl_handle || ! $csv_handle ) {
                        if ( $jsonl_handle ) {
                                fclose( $jsonl_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming temp-file handle teardown / end of write.
                        }
                        if ( $csv_handle ) {
                                fclose( $csv_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming temp-file handle teardown / end of write.
                        }
                        wp_delete_file( $tmp_jsonl );
                        wp_delete_file( $tmp_csv );
                        delete_transient( 'acpfw_generate_lock' );

                        return array(
                                'rows'         => 0,
                                'products'     => 0,
                                'generated_at' => 0,
                                'error'        => 'cannot_write_feed_files',
                        );
                }

                /*
                 * Plain UTF-8, NO BOM: strict CSV ingestion (OpenAI's parser,
                 * Python/Go validators) reads the first header cell literally,
                 * so a BOM turns item_id into \ufeffitem_id and every row is
                 * rejected for a missing required field. The JSONL file is
                 * BOM-free as well, for strict JSON parsers.
                 */

                // CSV header. All five fputcsv arguments are passed explicitly:
                // omitting $escape triggers a deprecation on PHP 8.4+.
                $columns = ACPFW_Product_Mapper::csv_columns();
                fputcsv( $csv_handle, $columns, ',', '"', '\\' );

                $row_count     = 0;
                $product_count = 0;
                $duplicates    = 0;
                $page          = 1;

                do {
                        $product_ids = wc_get_products(
                                array(
                                        'status'  => 'publish',
                                        'limit'   => self::PAGE_SIZE,
                                        'page'    => $page,
                                        'orderby' => 'ID',
                                        'order'   => 'ASC',
                                        'return'  => 'ids',
                                )
                        );

                        if ( ! is_array( $product_ids ) ) {
                                break;
                        }

                        /* Prime the post + meta caches once per page instead of
                         * letting every wc_get_product()/get_post_meta() below hit
                         * the database individually. */
                        _prime_post_caches( $product_ids, true, true );

                        foreach ( $product_ids as $product_id ) {
                                // Per-product opt-out wins over everything else.
                                if ( 'yes' === get_post_meta( $product_id, '_acpfw_exclude_from_feed', true ) ) {
                                        continue;
                                }

                                $product = wc_get_product( $product_id );

                                if ( ! $product instanceof WC_Product ) {
                                        continue;
                                }

                                $type = $product->get_type();

                                if ( 'grouped' === $type ) {
                                        continue; // Grouped products are containers, not purchasable rows.
                                }

                                /* Hidden, search-only and password-protected products
                                 * are not publicly part of the shop catalog and never
                                 * enter the machine-readable feed. */
                                if ( ! ACPFW_Plugin_Core::product_is_ai_public( $product ) ) {
                                        continue;
                                }

                                if ( 'variable' === $type ) {
                                        $children = $product->get_children();
                                        $seen     = array();

                                        if ( ! empty( $children ) ) {
                                                update_meta_cache( 'post', $children );
                                        }

                                        foreach ( $children as $child_id ) {
                                                $variation = wc_get_product( $child_id );

                                                if ( ! $variation instanceof WC_Product || ! $variation->is_purchasable() ) {
                                                        continue;
                                                }

                                                if ( 'yes' === get_post_meta( $child_id, '_acpfw_exclude_from_feed', true ) ) {
                                                        continue;
                                                }

                                                $row = ACPFW_Product_Mapper::map_variation( $variation, $product );

                                                /*
                                                 * Duplicate-variant guard: OpenAI expects unique
                                                 * option combinations per group. The completed
                                                 * variant_dict IS the option claim of a row (title
                                                 * and deep-link URL are derived from it), so two
                                                 * variations that complete to the same dict are the
                                                 * same combination and only the first is exported.
                                                 */
                                                $dict_for_dedupe = is_array( $row['variant_dict'] ) ? $row['variant_dict'] : array();
                                                ksort( $dict_for_dedupe );
                                                $dedupe_key = md5( $row['group_id'] . '|' . (string) wp_json_encode( $dict_for_dedupe ) );

                                                if ( isset( $seen[ $dedupe_key ] ) ) {
                                                        $duplicates++;
                                                        continue;
                                                }

                                                $seen[ $dedupe_key ] = true;

                                                self::write_row( $jsonl_handle, $csv_handle, $row );
                                                $row_count++;
                                        }

                                        $product_count++;
                                        continue;
                                }

                                // Simple + external products.
                                if ( ! $product->is_purchasable() ) {
                                        continue;
                                }

                                $row = ACPFW_Product_Mapper::map_simple( $product );
                                self::write_row( $jsonl_handle, $csv_handle, $row );
                                $row_count++;
                                $product_count++;
                        }

                        $page++;
                } while ( count( $product_ids ) === self::PAGE_SIZE );

                fclose( $jsonl_handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming temp-file handle teardown / end of write.
                fclose( $csv_handle );   // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Streaming temp-file handle teardown / end of write.

                /*
                 * Atomic swap: crawlers only ever see complete files. A failed
                 * move must never be silent — the stats would otherwise
                 * report success while the old (or missing) feed is served.
                 * WP_Filesystem::move() issues a real rename() on direct hosts.
                 */
                $fs       = ACPFW_Files::fs();
                $jsonl_ok = $fs->move( $tmp_jsonl, $jsonl_path, true );
                $csv_ok   = $fs->move( $tmp_csv, $csv_path, true );

                $stats = array(
                        'rows'         => $row_count,
                        'products'     => $product_count,
                        'duplicates'   => $duplicates,
                        'generated_at' => time(),
                );

                if ( ! $jsonl_ok || ! $csv_ok ) {
                        $stats['error'] = 'rename_failed';
                }

                update_option( 'acpfw_feed_stats', $stats, false );

                delete_transient( 'acpfw_generate_lock' );

                /**
                 * Fires after a full feed rebuild completes.
                 *
                 * @param array $stats Generation stats.
                 */
                do_action( 'acpfw_feeds_generated', $stats );

                return $stats;
        }

        /**
         * Stream one mapped row into both open file handles.
         *
         * @param resource $jsonl_handle JSONL file handle.
         * @param resource $csv_handle   CSV file handle.
         * @param array    $row          Mapped row.
         * @return void
         */
        private static function write_row( $jsonl_handle, $csv_handle, $row ) {
                // JSONL: omit empty optional fields, keep explicit booleans.
                $jsonl_row = array();

                foreach ( $row as $key => $value ) {
                        if ( '' === $value || null === $value ) {
                                continue;
                        }
                        $jsonl_row[ $key ] = $value;
                }

                fwrite( $jsonl_handle, wp_json_encode( $jsonl_row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Streaming write to the temp file opened above.

                // CSV: serialize nested values as JSON strings, in column order.
                $csv_row = array();
                foreach ( array_keys( self::csv_column_keys() ) as $column ) {
                        $value = isset( $row[ $column ] ) ? $row[ $column ] : '';

                        if ( is_array( $value ) ) {
                                $value = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
                        } elseif ( is_bool( $value ) ) {
                                $value = $value ? 'true' : 'false';
                        }

                        $csv_row[] = $value;
                }

                fputcsv( $csv_handle, $csv_row, ',', '"', '\\' );
        }

        /**
         * Column key list (avoids rebuilding the full skeleton each row).
         *
         * @return array
         */
        private static function csv_column_keys() {
                static $keys = null;

                if ( null === $keys ) {
                        $keys = array_fill_keys( ACPFW_Product_Mapper::csv_columns(), true );
                }

                return $keys;
        }
}
