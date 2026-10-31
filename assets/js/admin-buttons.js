/**
 * AI Product Feed & Agentic Commerce - admin behavior.
 *
 * Click-to-select for the read-only URL fields (feed URLs, discovery
 * endpoints, external cron URL). Replaces the former inline onclick
 * attributes with one delegated listener.
 *
 * @package ACPFW
 */

( function () {
        'use strict';

        document.addEventListener( 'click', function ( event ) {
                var field = event.target.closest( '.acpfw-url-input' );

                if ( field ) {
                        field.select();
                }
        } );
}() );
