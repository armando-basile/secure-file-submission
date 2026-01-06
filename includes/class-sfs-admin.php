<?php
/**
 * Admin Interface Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_post_sfs_delete_submission', array($this, 'handle_delete_submission'));
        
        // Add capability to administrators
        add_action('admin_init', array($this, 'add_capabilities'));
    }
    
    /**
     * Add manage_file_submissions capability to administrator role
     */
    public function add_capabilities() {
        $role = get_role('administrator');
        if ($role && !$role->has_cap('manage_file_submissions')) {
            $role->add_cap('manage_file_submissions');
        }
    }
    
    /**
     * Add admin menu pages
     */
    public function add_admin_menu() {
        // Menu principale - usa slug 'file-submissions' (non tradotto) per classi CSS prevedibili
        add_menu_page(
            __('File Submissions', 'secure-file-submission'),  // Page title (tradotto per utente)
            __('File Submissions', 'secure-file-submission'),  // Menu title (tradotto per utente)
            'manage_file_submissions',
            'file-submissions',  // Slug in inglese (genera classe CSS)
            array($this, 'render_submissions_page'),
            'dashicons-upload',
            30
        );
        
        // Rinomina il primo submenu (default)
        add_submenu_page(
            'file-submissions',  // Parent slug
            __('Submissions', 'secure-file-submission'),
            __('Submissions', 'secure-file-submission'),
            'manage_file_submissions',
            'file-submissions',  // Stesso slug del parent
            array($this, 'render_submissions_page')
        );
        
        // Submenu Settings
        add_submenu_page(
            'file-submissions',  // Parent slug
            __('Settings', 'secure-file-submission'),
            __('Settings', 'secure-file-submission'),
            'manage_file_submissions',
            'sfs-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('sfs_settings', 'sfs_max_file_size');
        register_setting('sfs_settings', 'sfs_max_archive_size');
        register_setting('sfs_settings', 'sfs_recaptcha_site_key');
        register_setting('sfs_settings', 'sfs_recaptcha_secret_key');
        register_setting('sfs_settings', 'sfs_admin_email');
        register_setting('sfs_settings', 'sfs_reply_to_email');
        register_setting('sfs_settings', 'sfs_email_subject_admin');
        register_setting('sfs_settings', 'sfs_email_subject_user');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Load on our plugin pages (file-submissions and sfs-settings)
        if (strpos($hook, 'file-submissions') !== false || strpos($hook, 'sfs-settings') !== false) {
            wp_enqueue_style('sfs-admin', SFS_PLUGIN_URL . 'assets/css/admin.css', array(), SFS_VERSION);
            wp_enqueue_script('sfs-admin', SFS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SFS_VERSION, true);
            
            wp_localize_script('sfs-admin', 'sfsAdmin', array(
                'confirmDelete' => __('Sei sicuro di voler eliminare questa submission? Questa azione è irreversibile.', 'secure-file-submission'),
            ));
        }
    }
    
    /**
     * Render submissions list page
     */
    public function render_submissions_page() {
        // Check user capabilities
        if (!current_user_can('manage_file_submissions')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'secure-file-submission'));
        }
        
        $db = new SFS_Database();
        
        // Handle search
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        // Pagination
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        
        $submissions = $db->get_submissions($per_page, $current_page, $search);
        $total_items = $db->get_total_count($search);
        $total_pages = ceil($total_items / $per_page);
        
        // Get archive statistics
        $file_handler = new SFS_File_Handler();
        $total_size = $file_handler->get_total_uploads_size();
        
        // Calculate remaining space based on archive limit (not disk space)
        $max_archive_size_mb = get_option('sfs_max_archive_size', 2048);
        $max_archive_size_bytes = $max_archive_size_mb * 1024 * 1024;
        $free_space = $max_archive_size_bytes - $total_size;
        
        include SFS_PLUGIN_DIR . 'admin/submissions-list.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_file_submissions')) {
            wp_die(__('Non hai i permessi per accedere a questa pagina.', 'secure-file-submission'));
        }
        
        // Get current disk usage
        $file_handler = new SFS_File_Handler();
        $total_size = $file_handler->get_total_uploads_size();
        
        $upload_dir = wp_upload_dir();
        $secure_dir = $upload_dir['basedir'] . '/secure-submissions';
        $free_space = disk_free_space($secure_dir);
        
        include SFS_PLUGIN_DIR . 'admin/settings.php';
    }
    
    /**
     * Handle submission deletion
     */
    public function handle_delete_submission() {
        // Check user capabilities
        if (!current_user_can('manage_file_submissions')) {
            wp_die(__('Non hai i permessi per eseguire questa azione.', 'secure-file-submission'));
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'sfs_delete_submission')) {
            wp_die(__('Verifica di sicurezza fallita.', 'secure-file-submission'));
        }
        
        $submission_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        
        if ($submission_id) {
            $db = new SFS_Database();
            $submission = $db->get_submission($submission_id);
            
            if ($submission) {
                // Delete file from server
                $file_handler = new SFS_File_Handler();
                $file_handler->delete_file($submission['file_path']);
                
                // Delete from database
                $db->delete_submission($submission_id);
                
                wp_redirect(add_query_arg(array(
                    'page' => 'file-submissions',
                    'deleted' => '1'
                ), admin_url('admin.php')));
                exit;
            }
        }
        
        wp_redirect(add_query_arg('page', 'file-submissions', admin_url('admin.php')));
        exit;
    }
}
