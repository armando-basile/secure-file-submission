<?php
/**
 * Fired during plugin activation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Activator {
    
    public static function activate() {
        self::create_tables();
        self::create_upload_directory();
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'sfs_submissions';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            cognome varchar(100) NOT NULL,
            nome varchar(100) NOT NULL,
            data_nascita date NOT NULL,
            comune_nascita varchar(100) NOT NULL,
            codice_fiscale varchar(16) NOT NULL,
            comune_residenza varchar(100) NOT NULL,
            indirizzo_residenza varchar(255) NOT NULL,
            telefono varchar(20) NOT NULL,
            email varchar(100) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_size bigint(20) NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            submitted_at datetime NOT NULL,
            status varchar(20) DEFAULT 'pending',
            notes text,
            PRIMARY KEY  (id),
            KEY codice_fiscale (codice_fiscale),
            KEY email (email),
            KEY submitted_at (submitted_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $secure_dir = $upload_dir['basedir'] . '/secure-submissions';
        
        if (!file_exists($secure_dir)) {
            wp_mkdir_p($secure_dir);
            
            // Create .htaccess to protect directory
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files ~ \"\\.(zip)$\">\n";
            $htaccess_content .= "    Order allow,deny\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</Files>";
            
            file_put_contents($secure_dir . '/.htaccess', $htaccess_content);
            
            // Create empty index.php
            file_put_contents($secure_dir . '/index.php', '<?php // Silence is golden');
        }
    }
    
    private static function set_default_options() {
        $defaults = array(
            'sfs_max_file_size' => 524288000, // 500MB in bytes
            'sfs_min_free_space' => 2048, // 2GB in MB
            'sfs_recaptcha_site_key' => '',
            'sfs_recaptcha_secret_key' => '',
            'sfs_admin_email' => get_option('admin_email'),
            'sfs_email_subject_admin' => 'Nuova Submission File Ricevuta',
            'sfs_email_subject_user' => 'Conferma Ricezione Richiesta',
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                add_option($key, $value);
            }
        }
    }
}
