<?php
defined( 'ABSPATH' ) || exit;

/**
 * Centralised settings defaults and helper.
 */
class BLS_Settings {

    /**
     * Returns the full settings array merged with defaults.
     *
     * @return array
     */
    public static function get(): array {
        return wp_parse_args(
            (array) get_option( BLS_OPTION, [] ),
            self::defaults()
        );
    }

    /**
     * Default values for every setting.
     *
     * @return array
     */
    public static function defaults(): array {
        return [
            /* ── Taxonomy ───────────────────────────────────── */
            'taxonomy'          => 'product_brand',   // slug – editable in case the site uses a custom one

            /* ── Layout ─────────────────────────────────────── */
            'direction'         => 'vertical',        // vertical | horizontal | grid
            'columns'           => 3,                 // grid columns (1-6)
            'order'             => 'ASC',             // ASC | DESC
            'orderby'           => 'name',            // name | count | slug | term_order
            'hide_empty'        => '1',               // 1 = hide brands with no products

            /* ── Container ───────────────────────────────────── */
            'container_bg'      => '#ffffff',
            'container_padding' => '16px',
            'container_radius'  => '0px',

            /* ── Item ───────────────────────────────────────── */
            'item_padding'      => '8px 12px',
            'item_spacing'      => '6px',
            'item_radius'       => '4px',
            'item_bg'           => 'transparent',
            'item_bg_hover'     => '#f5f5f5',

            /* ── Typography ──────────────────────────────────── */
            'font_family'       => 'inherit',
            'font_size'         => '14px',
            'font_weight'       => '400',
            'text_transform'    => 'none',            // none | uppercase | lowercase | capitalize
            'line_height'       => '1.5',

            /* ── Colours ─────────────────────────────────────── */
            'text_color'        => '#333333',
            'text_color_hover'  => '#000000',
            'bullet_color'      => '#333333',
            'border_color'      => 'transparent',
            'border_color_hover'=> '#cccccc',

            /* ── Bullet / prefix ─────────────────────────────── */
            'bullet_style'      => 'none',            // none | disc | circle | square | decimal | custom
            'bullet_custom'     => '›',               // used when bullet_style = custom

            /* ── Divider ─────────────────────────────────────── */
            'show_divider'      => '0',
            'divider_color'     => '#e0e0e0',
            'divider_size'      => '1px',

            /* ── Show count ──────────────────────────────────── */
            'show_count'        => '0',
            'count_color'       => '#999999',
            'count_size'        => '11px',

            /* ── Show logo ───────────────────────────────────── */
            'show_logo'         => '0',
            'logo_width'        => '60px',
            'logo_height'       => 'auto',

            /* ── Link behaviour ──────────────────────────────── */
            'link_target'       => '_self',           // _self | _blank
        ];
    }
}
