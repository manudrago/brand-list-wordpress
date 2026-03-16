<?php
defined( 'ABSPATH' ) || exit;

/**
 * Registers [brand_list] shortcode and handles frontend rendering.
 *
 * Shortcode attributes (all optional, fall back to admin settings):
 *   taxonomy    – taxonomy slug (default: product_brand)
 *   order       – ASC | DESC
 *   orderby     – name | count | slug
 *   hide_empty  – 1 | 0
 *   direction   – vertical | horizontal | grid
 *   columns     – 1-6  (grid only)
 *   show_count  – 1 | 0
 *   show_logo   – 1 | 0
 *   limit       – max brands to show (0 = all)
 *   include     – comma-separated term IDs to include
 *   exclude     – comma-separated term IDs to exclude
 *   title       – optional heading above the list
 *   class       – extra CSS class on the wrapper
 */
class BLS_Shortcode {

    public function __construct() {
        add_shortcode( 'brand_list', [ $this, 'render' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
    }

    /* ------------------------------------------------------------------ */
    /*  Assets                                                              */
    /* ------------------------------------------------------------------ */

    public function enqueue(): void {
        wp_register_style(
            'bls-frontend',
            BLS_URL . 'assets/css/frontend.css',
            [],
            BLS_VERSION
        );
        // Dynamic inline CSS is added when the shortcode is actually used
    }

    /* ------------------------------------------------------------------ */
    /*  Shortcode                                                           */
    /* ------------------------------------------------------------------ */

    public function render( $atts ): string {
        $s = BLS_Settings::get();

        $atts = shortcode_atts(
            [
                'taxonomy'   => $s['taxonomy'],
                'order'      => $s['order'],
                'orderby'    => $s['orderby'],
                'hide_empty' => $s['hide_empty'],
                'direction'  => $s['direction'],
                'columns'    => $s['columns'],
                'show_count' => $s['show_count'],
                'show_logo'  => $s['show_logo'],
                'limit'      => 0,
                'include'    => '',
                'exclude'    => '',
                'title'      => '',
                'class'      => '',
            ],
            $atts,
            'brand_list'
        );

        /* ── Enqueue + inject dynamic CSS ──────────────────────────── */
        wp_enqueue_style( 'bls-frontend' );
        $this->add_inline_css( $s );

        /* ── Query terms ──────────────────────────────────────────── */
        $query_args = [
            'taxonomy'   => sanitize_key( $atts['taxonomy'] ),
            'hide_empty' => (bool) $atts['hide_empty'],
            'order'      => in_array( strtoupper( $atts['order'] ), [ 'ASC', 'DESC' ], true ) ? strtoupper( $atts['order'] ) : 'ASC',
            'orderby'    => sanitize_key( $atts['orderby'] ),
        ];

        if ( ! empty( $atts['include'] ) ) {
            $query_args['include'] = wp_parse_id_list( $atts['include'] );
        }
        if ( ! empty( $atts['exclude'] ) ) {
            $query_args['exclude'] = wp_parse_id_list( $atts['exclude'] );
        }
        if ( (int) $atts['limit'] > 0 ) {
            $query_args['number'] = (int) $atts['limit'];
        }

        $terms = get_terms( $query_args );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p class="bls-no-brands">' . esc_html__( 'No brands found.', 'brand-list-shortcode' ) . '</p>';
        }

        /* ── Build HTML ──────────────────────────────────────────── */
        $direction = sanitize_key( $atts['direction'] );
        $columns   = max( 1, min( 6, (int) $atts['columns'] ) );
        $show_count = (bool) $atts['show_count'];
        $show_logo  = (bool) $atts['show_logo'];
        $extra_class = sanitize_html_class( $atts['class'] );
        $wrapper_classes = implode( ' ', array_filter( [
            'bls-wrapper',
            'bls-dir-' . $direction,
            $direction === 'grid' ? 'bls-cols-' . $columns : '',
            $extra_class,
        ] ) );

        ob_start();
        ?>
        <div class="<?php echo esc_attr( $wrapper_classes ); ?>">
            <?php if ( ! empty( $atts['title'] ) ) : ?>
                <h3 class="bls-title"><?php echo esc_html( $atts['title'] ); ?></h3>
            <?php endif; ?>
            <ul class="bls-list">
                <?php foreach ( $terms as $term ) :
                    $url  = get_term_link( $term );
                    $name = esc_html( $term->name );
                    $logo_html = '';

                    if ( $show_logo ) {
                        $thumbnail_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                        if ( $thumbnail_id ) {
                            $logo_html = wp_get_attachment_image(
                                $thumbnail_id,
                                'thumbnail',
                                false,
                                [ 'class' => 'bls-logo', 'alt' => $name ]
                            );
                        }
                    }
                    ?>
                    <li class="bls-item">
                        <a href="<?php echo esc_url( $url ); ?>"
                           class="bls-link"
                           target="<?php echo esc_attr( $s['link_target'] ); ?>">
                            <?php echo $logo_html; // already escaped by WP ?>
                            <span class="bls-name"><?php echo $name; ?></span>
                            <?php if ( $show_count ) : ?>
                                <span class="bls-count">(<?php echo (int) $term->count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    /* ------------------------------------------------------------------ */
    /*  Dynamic inline CSS from settings                                    */
    /* ------------------------------------------------------------------ */

    private function add_inline_css( array $s ): void {
        static $added = false;
        if ( $added ) return;
        $added = true;

        $cols_css = '';
        for ( $c = 1; $c <= 6; $c++ ) {
            $cols_css .= ".bls-cols-{$c} .bls-list { grid-template-columns: repeat({$c}, 1fr); }\n";
        }

        $gap = $this->sanitize_css_value( $s['item_spacing'] );

        $css = "
/* === Brand List Shortcode – dynamic styles === */

.bls-wrapper {
    background-color: {$this->esc_css_color( $s['container_bg'] )};
    padding: {$this->sanitize_css_value( $s['container_padding'] )};
    border-radius: {$this->sanitize_css_value( $s['container_radius'] )};
    font-family: {$this->sanitize_css_font( $s['font_family'] )};
    box-sizing: border-box;
}

.bls-title {
    margin-top: 0;
}

/* List resets */
.bls-list {
    list-style: none;
    margin: 0;
    padding: 0;
    gap: {$gap};
}

/* Vertical (default) */
.bls-dir-vertical .bls-list {
    display: flex;
    flex-direction: column;
}

/* Horizontal */
.bls-dir-horizontal .bls-list {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
}

/* Grid */
.bls-dir-grid .bls-list {
    display: grid;
}
{$cols_css}

/* Item */
.bls-item {
    display: block;
    border: 1px solid {$this->esc_css_color( $s['border_color'] )};
    border-radius: {$this->sanitize_css_value( $s['item_radius'] )};
    background-color: {$this->esc_css_color( $s['item_bg'] )};
}

.bls-item:hover {
    background-color: {$this->esc_css_color( $s['item_bg_hover'] )};
    border-color: {$this->esc_css_color( $s['border_color_hover'] )};
}

/* Divider */
" . ( $s['show_divider'] === '1' ? "
.bls-dir-vertical  .bls-item + .bls-item { border-top: {$this->sanitize_css_value( $s['divider_size'] )} solid {$this->esc_css_color( $s['divider_color'] )}; }
.bls-dir-horizontal .bls-item + .bls-item { border-left: {$this->sanitize_css_value( $s['divider_size'] )} solid {$this->esc_css_color( $s['divider_color'] )}; }
" : '' ) . "

/* Link */
.bls-link {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: {$this->sanitize_css_value( $s['item_padding'] )};
    text-decoration: none;
    color: {$this->esc_css_color( $s['text_color'] )};
    font-size: {$this->sanitize_css_value( $s['font_size'] )};
    font-weight: {$this->sanitize_css_value( $s['font_weight'] )};
    line-height: {$this->sanitize_css_value( $s['line_height'] )};
    text-transform: {$this->sanitize_css_value( $s['text_transform'] )};
}

.bls-link:hover,
.bls-link:focus {
    color: {$this->esc_css_color( $s['text_color_hover'] )};
    text-decoration: none;
}

/* Bullet */
" . ( $s['bullet_style'] !== 'none' ? "
.bls-link::before {
    content: '" . $this->bullet_content( $s ) . "';
    color: {$this->esc_css_color( $s['bullet_color'] )};
    font-style: normal;
    flex-shrink: 0;
}
" : '' ) . "

/* Logo */
.bls-logo {
    width: {$this->sanitize_css_value( $s['logo_width'] )};
    height: {$this->sanitize_css_value( $s['logo_height'] )};
    object-fit: contain;
    flex-shrink: 0;
}

/* Count badge */
.bls-count {
    color: {$this->esc_css_color( $s['count_color'] )};
    font-size: {$this->sanitize_css_value( $s['count_size'] )};
    margin-left: 4px;
}

.bls-no-brands {
    color: #999;
    font-style: italic;
}
";
        wp_add_inline_style( 'bls-frontend', $css );
    }

    /* ------------------------------------------------------------------ */
    /*  CSS helpers                                                         */
    /* ------------------------------------------------------------------ */

    private function esc_css_color( string $val ): string {
        $val = trim( $val );
        if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $val ) ) {
            return $val;
        }
        if ( preg_match( '/^rgba?\([\d,.\s%]+\)$/', $val ) ) {
            return $val;
        }
        if ( $val === 'transparent' || $val === 'inherit' || $val === 'currentColor' ) {
            return $val;
        }
        return 'inherit';
    }

    private function sanitize_css_value( string $val ): string {
        return preg_replace( '/[^a-zA-Z0-9\s%.,\-]/', '', trim( $val ) );
    }

    private function sanitize_css_font( string $val ): string {
        return preg_replace( '/[^a-zA-Z0-9\s\-,\'".]/', '', trim( $val ) );
    }

    private function bullet_content( array $s ): string {
        if ( $s['bullet_style'] === 'custom' ) {
            return addslashes( $s['bullet_custom'] );
        }
        $map = [
            'disc'    => '•',
            'circle'  => '◦',
            'square'  => '▪',
            'decimal' => '',   // counters not supported via ::before in list shortcode context
        ];
        return addslashes( $map[ $s['bullet_style'] ] ?? '•' );
    }
}
