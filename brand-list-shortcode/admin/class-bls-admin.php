<?php
defined( 'ABSPATH' ) || exit;

/**
 * Admin settings page for Brand List Shortcode.
 *
 * Registered under  Settings › Brand List
 */
class BLS_Admin {

    public function __construct() {
        add_action( 'admin_menu',       [ $this, 'add_menu' ] );
        add_action( 'admin_init',       [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Menu                                                                */
    /* ------------------------------------------------------------------ */

    public function add_menu(): void {
        add_options_page(
            __( 'Brand List Settings', 'brand-list-shortcode' ),
            __( 'Brand List', 'brand-list-shortcode' ),
            'manage_options',
            'brand-list-shortcode',
            [ $this, 'render_page' ]
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Settings API registration                                           */
    /* ------------------------------------------------------------------ */

    public function register_settings(): void {
        register_setting(
            'bls_settings_group',
            BLS_OPTION,
            [ 'sanitize_callback' => [ $this, 'sanitize' ] ]
        );
    }

    public function sanitize( $input ): array {
        $clean    = [];
        $defaults = BLS_Settings::defaults();

        $text_fields = [
            'taxonomy', 'container_padding', 'container_radius',
            'item_padding', 'item_spacing', 'item_radius',
            'font_family', 'font_size', 'font_weight', 'line_height',
            'logo_width', 'logo_height',
            'bullet_custom', 'divider_size', 'count_size',
            'count_color', 'divider_color',
        ];
        $color_fields = [
            'container_bg', 'item_bg', 'item_bg_hover',
            'text_color', 'text_color_hover', 'bullet_color',
            'border_color', 'border_color_hover',
        ];
        $select_fields = [
            'direction'      => [ 'vertical', 'horizontal', 'grid' ],
            'order'          => [ 'ASC', 'DESC' ],
            'orderby'        => [ 'name', 'count', 'slug', 'term_order' ],
            'text_transform' => [ 'none', 'uppercase', 'lowercase', 'capitalize' ],
            'bullet_style'   => [ 'disc', 'circle', 'square', 'decimal', 'none', 'custom' ],
            'link_target'    => [ '_self', '_blank' ],
        ];
        $int_fields    = [ 'columns' ];
        $bool_fields   = [ 'hide_empty', 'show_bullet', 'show_divider', 'show_count', 'show_logo' ];

        foreach ( $text_fields as $key ) {
            $clean[ $key ] = isset( $input[ $key ] )
                ? sanitize_text_field( $input[ $key ] )
                : $defaults[ $key ];
        }

        foreach ( $color_fields as $key ) {
            $val = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';
            if ( ! $val && isset( $input[ $key ] ) ) {
                // allow named / rgba values that sanitize_hex_color would strip
                $raw = sanitize_text_field( $input[ $key ] );
                if ( preg_match( '/^rgba?\([\d,.\s%]+\)$/', $raw ) || in_array( $raw, [ 'transparent', 'inherit' ], true ) ) {
                    $val = $raw;
                }
            }
            $clean[ $key ] = $val ?: $defaults[ $key ];
        }

        foreach ( $select_fields as $key => $allowed ) {
            $clean[ $key ] = in_array( $input[ $key ] ?? '', $allowed, true )
                ? $input[ $key ]
                : $defaults[ $key ];
        }

        foreach ( $int_fields as $key ) {
            $clean[ $key ] = isset( $input[ $key ] ) ? max( 1, (int) $input[ $key ] ) : $defaults[ $key ];
        }

        foreach ( $bool_fields as $key ) {
            $clean[ $key ] = ! empty( $input[ $key ] ) ? '1' : '0';
        }

        return $clean;
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                              */
    /* ------------------------------------------------------------------ */

    public function enqueue( string $hook ): void {
        if ( $hook !== 'settings_page_brand-list-shortcode' ) return;

        wp_enqueue_style(  'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        wp_enqueue_style(
            'bls-admin',
            BLS_URL . 'assets/css/admin.css',
            [],
            BLS_VERSION
        );
        wp_enqueue_script(
            'bls-admin',
            BLS_URL . 'assets/js/admin.js',
            [ 'jquery', 'wp-color-picker' ],
            BLS_VERSION,
            true
        );
        wp_localize_script( 'bls-admin', 'blsAdmin', [
            'previewNonce' => wp_create_nonce( 'bls_preview' ),
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
        ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Page render                                                         */
    /* ------------------------------------------------------------------ */

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) return;
        $s = BLS_Settings::get();
        ?>
        <div class="wrap bls-admin-wrap">
            <h1><?php esc_html_e( 'Brand List Shortcode – Settings', 'brand-list-shortcode' ); ?></h1>

            <div class="bls-shortcode-info notice notice-info inline">
                <p>
                    <?php esc_html_e( 'Use the shortcode', 'brand-list-shortcode' ); ?>
                    <code>[brand_list]</code>
                    <?php esc_html_e( 'anywhere – pages, posts, widgets, mega menus, FSE blocks.', 'brand-list-shortcode' ); ?>
                    <br>
                    <?php esc_html_e( 'Override any setting inline:', 'brand-list-shortcode' ); ?>
                    <code>[brand_list direction="horizontal" columns="4" show_count="1" title="Our Brands"]</code>
                </p>
            </div>

            <div class="bls-layout">
                <!-- ─── Settings form ─────────────────────── -->
                <form method="post" action="options.php" id="bls-settings-form">
                    <?php settings_fields( 'bls_settings_group' ); ?>

                    <?php $this->render_section( __( '⚙ General', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_text( 'taxonomy', __( 'Taxonomy slug', 'brand-list-shortcode' ), $s['taxonomy'],
                            __( 'WooCommerce Brands uses <code>product_brand</code>. YITH uses <code>yith_product_brand</code>. Check Appearance › Menus or a plugin\'s docs if unsure.', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_select( 'orderby', __( 'Order by', 'brand-list-shortcode' ), $s['orderby'], [
                            'name'       => __( 'Name', 'brand-list-shortcode' ),
                            'count'      => __( 'Product count', 'brand-list-shortcode' ),
                            'slug'       => __( 'Slug', 'brand-list-shortcode' ),
                            'term_order' => __( 'Custom order (term_order)', 'brand-list-shortcode' ),
                        ] ); ?>
                        <?php $this->field_select( 'order', __( 'Direction', 'brand-list-shortcode' ), $s['order'], [
                            'ASC'  => __( 'Ascending (A→Z)', 'brand-list-shortcode' ),
                            'DESC' => __( 'Descending (Z→A)', 'brand-list-shortcode' ),
                        ] ); ?>
                        <?php $this->field_checkbox( 'hide_empty', __( 'Hide brands with no products', 'brand-list-shortcode' ), $s['hide_empty'] ); ?>
                        <?php $this->field_select( 'link_target', __( 'Link target', 'brand-list-shortcode' ), $s['link_target'], [
                            '_self'  => __( 'Same tab', 'brand-list-shortcode' ),
                            '_blank' => __( 'New tab', 'brand-list-shortcode' ),
                        ] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '📐 Layout', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_select( 'direction', __( 'Display direction', 'brand-list-shortcode' ), $s['direction'], [
                            'vertical'   => __( 'Vertical list', 'brand-list-shortcode' ),
                            'horizontal' => __( 'Horizontal (inline)', 'brand-list-shortcode' ),
                            'grid'       => __( 'Grid', 'brand-list-shortcode' ),
                        ] ); ?>
                        <?php $this->field_number( 'columns', __( 'Grid columns', 'brand-list-shortcode' ), $s['columns'], 1, 6,
                            __( 'Only applies when direction = Grid.', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_text( 'item_spacing', __( 'Gap between items', 'brand-list-shortcode' ), $s['item_spacing'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '📦 Container', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_color( 'container_bg',      __( 'Background colour', 'brand-list-shortcode' ), $s['container_bg'] ); ?>
                        <?php $this->field_text(  'container_padding', __( 'Padding', 'brand-list-shortcode' ),           $s['container_padding'] ); ?>
                        <?php $this->field_text(  'container_radius',  __( 'Border radius', 'brand-list-shortcode' ),     $s['container_radius'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '🔤 Typography', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_text(   'font_family',    __( 'Font family', 'brand-list-shortcode' ), $s['font_family'],
                            __( 'e.g. <code>inherit</code>, <code>Arial, sans-serif</code>, <code>"Open Sans", sans-serif</code>', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_text(   'font_size',      __( 'Font size', 'brand-list-shortcode' ),   $s['font_size'] ); ?>
                        <?php $this->field_text(   'font_weight',    __( 'Font weight', 'brand-list-shortcode' ), $s['font_weight'],
                            __( 'e.g. <code>400</code>, <code>600</code>, <code>bold</code>', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_text(   'line_height',    __( 'Line height', 'brand-list-shortcode' ), $s['line_height'] ); ?>
                        <?php $this->field_select( 'text_transform', __( 'Text transform', 'brand-list-shortcode' ), $s['text_transform'], [
                            'none'       => __( 'None', 'brand-list-shortcode' ),
                            'uppercase'  => __( 'UPPERCASE', 'brand-list-shortcode' ),
                            'lowercase'  => __( 'lowercase', 'brand-list-shortcode' ),
                            'capitalize' => __( 'Capitalize', 'brand-list-shortcode' ),
                        ] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '🎨 Colours', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_color( 'text_color',         __( 'Link colour', 'brand-list-shortcode' ),              $s['text_color'] ); ?>
                        <?php $this->field_color( 'text_color_hover',   __( 'Link colour (hover)', 'brand-list-shortcode' ),      $s['text_color_hover'] ); ?>
                        <?php $this->field_color( 'item_bg',            __( 'Item background', 'brand-list-shortcode' ),          $s['item_bg'] ); ?>
                        <?php $this->field_color( 'item_bg_hover',      __( 'Item background (hover)', 'brand-list-shortcode' ),  $s['item_bg_hover'] ); ?>
                        <?php $this->field_color( 'border_color',       __( 'Item border colour', 'brand-list-shortcode' ),       $s['border_color'] ); ?>
                        <?php $this->field_color( 'border_color_hover', __( 'Item border colour (hover)', 'brand-list-shortcode' ),$s['border_color_hover'] ); ?>
                        <?php $this->field_text(  'item_padding',       __( 'Item padding', 'brand-list-shortcode' ),             $s['item_padding'] ); ?>
                        <?php $this->field_text(  'item_radius',        __( 'Item border radius', 'brand-list-shortcode' ),       $s['item_radius'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '• Bullet', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_checkbox( 'show_bullet',  __( 'Show bullet', 'brand-list-shortcode' ),       $s['show_bullet'] ); ?>
                        <?php $this->field_select(   'bullet_style', __( 'Bullet style', 'brand-list-shortcode' ),      $s['bullet_style'], [
                            'disc'    => '• Disc',
                            'circle'  => '◦ Circle',
                            'square'  => '▪ Square',
                            'decimal' => '1. Decimal',
                            'none'    => __( 'None', 'brand-list-shortcode' ),
                            'custom'  => __( 'Custom character', 'brand-list-shortcode' ),
                        ] ); ?>
                        <?php $this->field_text(  'bullet_custom', __( 'Custom bullet character', 'brand-list-shortcode' ), $s['bullet_custom'],
                            __( 'Used only when bullet style = Custom. E.g. <code>›</code>, <code>→</code>, <code>★</code>', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_color( 'bullet_color',  __( 'Bullet colour', 'brand-list-shortcode' ),          $s['bullet_color'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '— Divider', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_checkbox( 'show_divider',  __( 'Show divider between items', 'brand-list-shortcode' ), $s['show_divider'] ); ?>
                        <?php $this->field_color(    'divider_color', __( 'Divider colour', 'brand-list-shortcode' ),             $s['divider_color'] ); ?>
                        <?php $this->field_text(     'divider_size',  __( 'Divider thickness', 'brand-list-shortcode' ),         $s['divider_size'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '🔢 Product count', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_checkbox( 'show_count',  __( 'Show product count', 'brand-list-shortcode' ), $s['show_count'] ); ?>
                        <?php $this->field_color(    'count_color', __( 'Count colour', 'brand-list-shortcode' ),       $s['count_color'] ); ?>
                        <?php $this->field_text(     'count_size',  __( 'Count font size', 'brand-list-shortcode' ),    $s['count_size'] ); ?>
                    <?php } ); ?>

                    <?php $this->render_section( __( '🖼 Brand logo', 'brand-list-shortcode' ), function() use ( $s ) { ?>
                        <?php $this->field_checkbox( 'show_logo',   __( 'Show brand logo', 'brand-list-shortcode' ),   $s['show_logo'],
                            __( 'Requires a thumbnail set on the brand taxonomy term.', 'brand-list-shortcode' ) ); ?>
                        <?php $this->field_text(     'logo_width',  __( 'Logo width', 'brand-list-shortcode' ),        $s['logo_width'] ); ?>
                        <?php $this->field_text(     'logo_height', __( 'Logo height', 'brand-list-shortcode' ),       $s['logo_height'] ); ?>
                    <?php } ); ?>

                    <?php submit_button( __( 'Save Settings', 'brand-list-shortcode' ) ); ?>
                </form>

                <!-- ─── Live preview ─────────────────────── -->
                <div class="bls-preview-pane">
                    <h2><?php esc_html_e( 'Live Preview', 'brand-list-shortcode' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'Saved styles are reflected here after you save.', 'brand-list-shortcode' ); ?></p>
                    <div class="bls-preview-frame">
                        <?php echo do_shortcode( '[brand_list limit="10"]' ); ?>
                    </div>
                </div>

            </div><!-- .bls-layout -->
        </div><!-- .wrap -->
        <?php
    }

    /* ------------------------------------------------------------------ */
    /*  Section / field helpers                                             */
    /* ------------------------------------------------------------------ */

    private function render_section( string $title, callable $fields ): void {
        ?>
        <div class="bls-section">
            <h2 class="bls-section-title"><?php echo esc_html( $title ); ?></h2>
            <table class="form-table bls-table" role="presentation">
                <?php $fields(); ?>
            </table>
        </div>
        <?php
    }

    private function field_text( string $key, string $label, string $value, string $desc = '' ): void {
        $id = 'bls_' . $key;
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <input type="text" id="<?php echo esc_attr( $id ); ?>"
                       name="<?php echo esc_attr( BLS_OPTION . '[' . $key . ']' ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                       class="regular-text">
                <?php if ( $desc ) : ?>
                    <p class="description"><?php echo wp_kses_post( $desc ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    private function field_number( string $key, string $label, $value, int $min, int $max, string $desc = '' ): void {
        $id = 'bls_' . $key;
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <input type="number" id="<?php echo esc_attr( $id ); ?>"
                       name="<?php echo esc_attr( BLS_OPTION . '[' . $key . ']' ); ?>"
                       value="<?php echo (int) $value; ?>"
                       min="<?php echo $min; ?>" max="<?php echo $max; ?>"
                       class="small-text">
                <?php if ( $desc ) : ?>
                    <p class="description"><?php echo wp_kses_post( $desc ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    private function field_select( string $key, string $label, string $value, array $options, string $desc = '' ): void {
        $id = 'bls_' . $key;
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <select id="<?php echo esc_attr( $id ); ?>"
                        name="<?php echo esc_attr( BLS_OPTION . '[' . $key . ']' ); ?>">
                    <?php foreach ( $options as $opt_val => $opt_label ) : ?>
                        <option value="<?php echo esc_attr( $opt_val ); ?>"
                            <?php selected( $value, $opt_val ); ?>>
                            <?php echo esc_html( $opt_label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ( $desc ) : ?>
                    <p class="description"><?php echo wp_kses_post( $desc ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    private function field_color( string $key, string $label, string $value ): void {
        $id = 'bls_' . $key;
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <input type="text" id="<?php echo esc_attr( $id ); ?>"
                       name="<?php echo esc_attr( BLS_OPTION . '[' . $key . ']' ); ?>"
                       value="<?php echo esc_attr( $value ); ?>"
                       class="bls-color-picker"
                       data-default-color="<?php echo esc_attr( $value ); ?>">
            </td>
        </tr>
        <?php
    }

    private function field_checkbox( string $key, string $label, string $value, string $desc = '' ): void {
        $id = 'bls_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html( $label ); ?></th>
            <td>
                <label for="<?php echo esc_attr( $id ); ?>">
                    <input type="checkbox" id="<?php echo esc_attr( $id ); ?>"
                           name="<?php echo esc_attr( BLS_OPTION . '[' . $key . ']' ); ?>"
                           value="1" <?php checked( $value, '1' ); ?>>
                    <?php esc_html_e( 'Enable', 'brand-list-shortcode' ); ?>
                </label>
                <?php if ( $desc ) : ?>
                    <p class="description"><?php echo wp_kses_post( $desc ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}
