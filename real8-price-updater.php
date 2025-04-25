<?php
/*
 * Plugin Name: REAL8 Price Updater
 * Description: Automatically updates the REAL8 WooCommerce product price based on Stellar Horizon trade data.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 * Text Domain: real8-price-updater
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Define plugin constants
define('REAL8_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REAL8_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load Composer autoloader
if (file_exists(REAL8_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once REAL8_PLUGIN_DIR . 'vendor/autoload.php';
} else {
    add_action('admin_notices', function() {
        echo '<div class="error"><p><strong>REAL8 Price Updater:</strong> Composer autoloader not found. Please run <code>composer install</code> in the plugin directory.</p></div>';
    });
    return;
}

// Load plugin classes
require_once REAL8_PLUGIN_DIR . 'includes/class-real8-price-updater.php';
require_once REAL8_PLUGIN_DIR . 'includes/class-real8-admin.php';

// Initialize the plugin
function real8_price_updater_init() {
    // Check if WooCommerce is active
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p><strong>REAL8 Price Updater:</strong> WooCommerce is required for this plugin to work.</p></div>';
        });
        return;
    }

    // Initialize core and admin classes
    $updater = new Real8_Price_Updater();
    if (is_admin()) {
        $admin = new Real8_Admin();
    }
}
add_action('plugins_loaded', 'real8_price_updater_init');
