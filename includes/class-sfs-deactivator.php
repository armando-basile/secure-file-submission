<?php
/**
 * Fired during plugin deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Deactivator {
    
    public static function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't delete tables or files on deactivation
        // This preserves data if plugin is temporarily deactivated
        // Data can be manually deleted from the admin interface if needed
    }
}
