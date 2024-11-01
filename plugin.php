<?php
/**
 * Plugin Name: Smart Collections by NAPPS
 * Plugin URI: https://napps.io/
 * Description: Smart collections for woocommerce
 * Version:     1.0.1
 * Author:      NAPPS
 * Author URI:  https://napps.io
 * Text Domain: smart-collections-by-napps
 * Domain Path: /languages
 *
 * @package napps
 */

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

define( 'NAPPS_SMARTCOLLECTIONS_FILE',  __FILE__ );
define( 'NAPPS_SMARTCOLLECTIONS_PATH', plugin_dir_path( NAPPS_SMARTCOLLECTIONS_FILE ) );

// Require composer.
require_once( plugin_dir_path(__FILE__) . '/vendor/autoload.php' );

use NappsSmartCollections\Loader;
Loader::init();
