<?php
/**
 * Plugin Name: Secure File Submission
 * Plugin URI: https://github.com/armando-basile/secure-file-submission
 * Description: Gestione sicura di invio file con validazione dati anagrafici e codice fiscale
 * Version: 1.0.0
 * Author: Armando Basile
 * Author URI: https://www.integrazioneweb.com/
 * License: GPL v2 or later
 * Text Domain: secure-file-submission
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SFS_VERSION', '1.0.0');
define('SFS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SFS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SFS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-activator.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-deactivator.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-database.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-codice-fiscale.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-email-validator.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-file-handler.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-admin.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-frontend.php';
require_once SFS_PLUGIN_DIR . 'includes/class-sfs-ajax.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('SFS_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('SFS_Deactivator', 'deactivate'));

// Load plugin text domain for translations
function sfs_load_textdomain() {
    load_plugin_textdomain('secure-file-submission', false, dirname(SFS_PLUGIN_BASENAME) . '/languages');
}
add_action('init', 'sfs_load_textdomain');

// Initialize the plugin
function sfs_init() {
    // Initialize admin interface
    if (is_admin()) {
        new SFS_Admin();
    }
    
    // Initialize frontend
    new SFS_Frontend();
    
    // Initialize AJAX handlers
    new SFS_AJAX();
}
add_action('plugins_loaded', 'sfs_init');

// Add custom role on plugin activation
function sfs_add_custom_role() {
    add_role(
        'file_submission_manager',
        __('File Submission Manager', 'secure-file-submission'),
        array(
            'read' => true,
            'manage_file_submissions' => true,
        )
    );
}
register_activation_hook(__FILE__, 'sfs_add_custom_role');

// Remove custom role on plugin deactivation
function sfs_remove_custom_role() {
    remove_role('file_submission_manager');
}
register_deactivation_hook(__FILE__, 'sfs_remove_custom_role');
