<?php
/**
 * Plugin Name: Language Proficiency Test
 * Plugin URI: 
 * Description: A comprehensive language proficiency test system for multiple languages
 * Version: 1.0.0
 * Author: Cem Alpay Taş @cemalpay
 * Text Domain: language-proficiency-test
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LPT_VERSION', '1.0.0');
define('LPT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LPT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once LPT_PLUGIN_DIR . 'includes/class-lpt-test-manager.php';
require_once LPT_PLUGIN_DIR . 'includes/class-lpt-database.php';

// Plugin activation hook
register_activation_hook(__FILE__, 'lpt_activate');

function lpt_activate() {
    // Create database tables
    LPT_Database::create_tables();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Initialize the plugin
function lpt_init() {
    // Load text domain for translations
    load_plugin_textdomain('language-proficiency-test', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize the test manager
    $test_manager = new LPT_Test_Manager();
    $test_manager->init();
}
add_action('plugins_loaded', 'lpt_init');

// Add admin menu
function lpt_admin_menu() {
    add_menu_page(
        __('Language Proficiency Tests', 'language-proficiency-test'),
        __('Language Tests', 'language-proficiency-test'),
        'manage_options',
        'language-proficiency-test',
        'lpt_admin_page',
        'dashicons-welcome-learn-more',
        30
    );
}
add_action('admin_menu', 'lpt_admin_menu');

// Admin page callback
function lpt_admin_page() {
    include LPT_PLUGIN_DIR . 'includes/admin/admin-page.php';
} 