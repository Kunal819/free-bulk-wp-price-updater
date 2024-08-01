<?php
/*
 * Plugin Name:       Free Bulk WP Price Updater     
 * Description:       This is a free plugin for updating WooCommerce product prices 
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Kunal Verma
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       free-bulk-wp-price-updater
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin path and URL
define('FBPE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('FBPE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include class files
include_once FBPE_PLUGIN_PATH . 'inc/class-free-bulk-price-editor.php';
include_once FBPE_PLUGIN_PATH . 'inc/class-ajax-handler.php'; // Include if you have AJAX handling in a separate class

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('Free_Bulk_Price_Editor', 'activate'));
register_deactivation_hook(__FILE__, array('Free_Bulk_Price_Editor', 'deactivate'));

// Initialize the plugin
add_action('plugins_loaded', function() {
    Free_Bulk_Price_Editor::get_instance();
});

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'fbpe_enqueue_admin_scripts');

function fbpe_enqueue_admin_scripts() {
    // Enqueue the script before localizing
    $script_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/admin/admin-script.js');
    wp_enqueue_script('fbpe-admin-script', FBPE_PLUGIN_URL . 'assets/js/admin/admin-script.js', array('jquery'), $script_version, true);


    // Localize the script after it has been enqueued
    wp_localize_script('fbpe-admin-script', 'fbpeAjax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('fbpe_nonce')
    ));
    
    // Enqueue the stylesheet
    wp_enqueue_style('fbpe-admin-style', FBPE_PLUGIN_URL . 'assets/css/admin-style.css', array(), $script_version);

}


