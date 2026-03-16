<?php
/**
 * Plugin Name: Brand List Shortcode
 * Plugin URI:  https://github.com/manudrago/brand-list-wordpress
 * Description: Displays WooCommerce product brands as a styled, linkable list. Drop it anywhere with [brand_list]. Fully customisable from the admin panel.
 * Version:     1.0.0
 * Author:      manudrago
 * Author URI:  https://github.com/manudrago
 * Text Domain: brand-list-shortcode
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 */

defined( 'ABSPATH' ) || exit;

define( 'BLS_VERSION',  '1.0.0' );
define( 'BLS_FILE',     __FILE__ );
define( 'BLS_DIR',      plugin_dir_path( __FILE__ ) );
define( 'BLS_URL',      plugin_dir_url( __FILE__ ) );
define( 'BLS_OPTION',   'bls_settings' );

require_once BLS_DIR . 'includes/class-bls-settings.php';
require_once BLS_DIR . 'includes/class-bls-shortcode.php';

if ( is_admin() ) {
    require_once BLS_DIR . 'admin/class-bls-admin.php';
    new BLS_Admin();
}

new BLS_Shortcode();
