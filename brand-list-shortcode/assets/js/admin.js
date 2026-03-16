/**
 * Brand List Shortcode – Admin JS
 * Initialises the WP colour picker on all .bls-color-picker inputs.
 */
( function ( $ ) {
    'use strict';

    $( function () {

        /* ── Colour pickers ── */
        $( '.bls-color-picker' ).wpColorPicker( {
            change: debounce( function () {
                // Optionally trigger a preview refresh here via AJAX in the future
            }, 300 ),
        } );

        /* ── Show/hide grid columns when direction changes ── */
        var $dirSelect    = $( '#bls_direction' );
        var $colRow       = $( '#bls_columns' ).closest( 'tr' );

        function toggleColumns() {
            if ( $dirSelect.val() === 'grid' ) {
                $colRow.show();
            } else {
                $colRow.hide();
            }
        }

        $dirSelect.on( 'change', toggleColumns );
        toggleColumns();

        /* ── Show/hide bullet custom char ── */
        var $bulletSelect  = $( '#bls_bullet_style' );
        var $bulletCustomRow = $( '#bls_bullet_custom' ).closest( 'tr' );

        function toggleBulletCustom() {
            if ( $bulletSelect.val() === 'custom' ) {
                $bulletCustomRow.show();
            } else {
                $bulletCustomRow.hide();
            }
        }

        $bulletSelect.on( 'change', toggleBulletCustom );
        toggleBulletCustom();

    } );

    /* ── Tiny debounce helper ── */
    function debounce( fn, delay ) {
        var t;
        return function () {
            clearTimeout( t );
            t = setTimeout( fn.bind( this, arguments ), delay );
        };
    }

} )( jQuery );
