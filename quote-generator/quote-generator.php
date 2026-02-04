<?php
/**
 * Plugin Name: Elementor Quote Generator
 * Description: Generate downloadable PDF quotations via Elementor popup or shortcode with drag-and-drop products.
 * Version: 2.5.0
 * Text Domain: eqg
 * Domain Path: /languages
 * Author: Genesis Jr
 */

if (!defined('ABSPATH')) exit;

define('EQG_PATH', plugin_dir_path(__FILE__));
define('EQG_URL', plugin_dir_url(__FILE__));
define('EQG_VERSION', '2.0.0');

// Load Composer autoloader
if (file_exists(EQG_PATH . 'vendors/autoload.php')) {
    require_once EQG_PATH . 'vendors/autoload.php';
} elseif (file_exists(EQG_PATH . 'vendor/autoload.php')) {
    require_once EQG_PATH . 'vendor/autoload.php';
}

// Load plugin files
require_once EQG_PATH . 'includes/class-quote-cpt.php';
require_once EQG_PATH . 'includes/class-pdf-generator.php';
require_once EQG_PATH . 'includes/class-shortcode.php';
require_once EQG_PATH . 'includes/class-ajax-handler.php';
require_once EQG_PATH . 'includes/class-email-handler.php';
require_once EQG_PATH . 'includes/class-admin-columns.php';
require_once EQG_PATH . 'includes/class-products.php';
require_once EQG_PATH . 'includes/class-product-admin.php';

// Create database tables on activation
register_activation_hook(__FILE__, 'eqg_create_tables');

function eqg_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Products table
    $products_table = $wpdb->prefix . 'eqg_products';
    $sql1 = "CREATE TABLE $products_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        product_name varchar(255) NOT NULL,
        description text,
        price decimal(10,2) NOT NULL DEFAULT 0.00,
        category varchar(100),
        status varchar(20) DEFAULT 'active',
        created_by int(11),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY product_name (product_name),
        KEY status (status),
        KEY category (category)
    ) $charset_collate;";

    // Quote items table (for multiple products per quote)
    $items_table = $wpdb->prefix . 'eqg_quote_items';
    $sql2 = "CREATE TABLE $items_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        quote_id int(11) NOT NULL,
        product_id int(11),
        product_name varchar(255) NOT NULL,
        quantity int(11) DEFAULT 1,
        unit_price decimal(10,2) NOT NULL,
        total_price decimal(10,2) NOT NULL,
        notes text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY quote_id (quote_id),
        KEY product_id (product_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql1);
    dbDelta($sql2);
    
    update_option('eqg_db_version', '2.0');
}

// Enqueue styles and scripts
add_action('wp_enqueue_scripts', function() {
    // Enqueue WordPress dashicons
    wp_enqueue_style('dashicons');
    
    // Enqueue plugin styles
    wp_enqueue_style('eqg-styles', EQG_URL . 'assets/css/style.css', [], EQG_VERSION);
    
    // Enqueue jQuery UI with draggable, droppable, and sortable
    wp_enqueue_script('jquery-ui-draggable');
    wp_enqueue_script('jquery-ui-droppable');
    wp_enqueue_script('jquery-ui-sortable');
    
    // Enqueue plugin script
    wp_enqueue_script('eqg-script', EQG_URL . 'assets/js/script.js', [
        'jquery', 
        'jquery-ui-draggable', 
        'jquery-ui-droppable', 
        'jquery-ui-sortable'
    ], EQG_VERSION, true);
    
    // Localize script with AJAX data
    wp_localize_script('eqg-script', 'eqgAjax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('eqg_ajax_nonce')
    ]);
});

// Register Elementor widget
add_action('elementor/widgets/register', function ($widgets_manager) {
    require_once EQG_PATH . 'widgets/class-quote-widget.php';
    $widgets_manager->register(new \EQG_Quote_Widget());
});

// Load text domain
add_action('plugins_loaded', function() {
    load_plugin_textdomain('eqg', false, dirname(plugin_basename(__FILE__)) . '/languages');
});