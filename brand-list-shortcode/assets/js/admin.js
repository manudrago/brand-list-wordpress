/**
 * Brand List Shortcode – Admin JS
 * Initialises the WP colour picker on all .bls-color-picker inputs.
 */
( function ( $ ) {
    'use strict';

    $( function () {

        /* ── Colour pickers ── */
        $( '.bls-color-picker' ).wpColorPicker( {
            change: debounce( function ( event, ui ) {
                // If this picker is paired with a hidden input, keep it in sync
                var hiddenId = $( this ).data( 'hiddenId' );
                if ( hiddenId ) {
                    $( '#' + hiddenId ).val( ui.color.toString() );
                }
            }, 300 ),
        } );

        /* ── Transparent background toggles ── */
        $( '.bls-transparent-cb' ).each( function () {
            var $cb       = $( this );
            var $picker   = $( '#' + $cb.data( 'pickerId' ) );
            var $pickerWrap = $picker.closest( '.wp-picker-container' );
            if ( $cb.is( ':checked' ) ) {
                $pickerWrap.hide();
            }
        } );

        $( '.bls-transparent-cb' ).on( 'change', function () {
            var $cb       = $( this );
            var $picker   = $( '#' + $cb.data( 'pickerId' ) );
            var $hidden   = $( '#' + $cb.data( 'hiddenId' ) );
            var $pickerWrap = $picker.closest( '.wp-picker-container' );

            if ( $cb.is( ':checked' ) ) {
                $hidden.val( 'transparent' );
                $pickerWrap.hide();
            } else {
                var color = $picker.wpColorPicker( 'color' ) || $picker.data( 'defaultColor' ) || '#ffffff';
                $hidden.val( color );
                $pickerWrap.show();
            }
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
