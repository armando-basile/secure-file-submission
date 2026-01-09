<?php
/**
 * Fired during plugin deactivation
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Deactivator {
    
    public static function deactivate() {
        self::remove_custom_role();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Note: We don't delete tables or files on deactivation
        // This preserves data if plugin is temporarily deactivated
        // Data can be manually deleted from the admin interface if needed
    }
    
    /**
     * Remove custom role on deactivation
     */
    private static function remove_custom_role() {
        remove_role('file_submission_manager');
    }
}
