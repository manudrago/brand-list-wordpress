===  Brand List Shortcode ===
Contributors: manudrago
Tags: woocommerce, brands, shortcode, product brand, mega menu
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display a fully styled, linkable list of WooCommerce product brands anywhere with one shortcode.

== Description ==

**Brand List Shortcode** lets you drop `[brand_list]` into any page, post, widget, mega menu, or Full Site Editor block and instantly output a styled list of every brand (product taxonomy term) with its archive link.

Every style aspect is controllable from **Settings › Brand List** — no CSS knowledge needed.

= Features =
* Supports any product taxonomy (WooCommerce Brands `product_brand`, YITH `yith_product_brand`, custom slugs)
* Three display modes: **Vertical list**, **Horizontal inline**, **Grid**
* Configurable order (A→Z, Z→A) and orderby (name, count, slug, custom)
* Full colour control: text, hover, background, border (normal + hover)
* Typography: font family, size, weight, line-height, text-transform
* Optional bullet with 5 styles + custom character
* Optional divider between items
* Optional product count badge
* Optional brand logo (reads `thumbnail_id` term meta set by brand plugins)
* All settings overridable per-instance via shortcode attributes

= Shortcode attributes =

    [brand_list
        taxonomy="product_brand"
        direction="vertical"     (vertical | horizontal | grid)
        columns="3"              (grid only)
        order="ASC"              (ASC | DESC)
        orderby="name"           (name | count | slug | term_order)
        hide_empty="1"           (1 | 0)
        show_count="0"           (1 | 0)
        show_logo="0"            (1 | 0)
        limit="0"                (0 = all)
        include="1,5,9"          (term IDs to include)
        exclude="2,4"            (term IDs to exclude)
        title="Our Brands"       (optional heading)
        class="my-custom-class"  (extra wrapper class)
    ]

== Installation ==

1. Upload the `brand-list-shortcode` folder to `/wp-content/plugins/`
2. Activate the plugin
3. Go to **Settings › Brand List** to customise the appearance
4. Place `[brand_list]` wherever you need it

== Changelog ==

= 1.0.0 =
* Initial release
