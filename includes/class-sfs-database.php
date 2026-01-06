<?php
/**
 * Database operations handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Database {
    
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sfs_submissions';
    }
    
    /**
     * Insert a new submission
     */
    public function insert_submission($data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'cognome' => sanitize_text_field($data['cognome']),
                'nome' => sanitize_text_field($data['nome']),
                'data_nascita' => sanitize_text_field($data['data_nascita']),
                'comune_nascita' => sanitize_text_field($data['comune_nascita']),
                'codice_fiscale' => strtoupper(sanitize_text_field($data['codice_fiscale'])),
                'comune_residenza' => sanitize_text_field($data['comune_residenza']),
                'indirizzo_residenza' => sanitize_text_field($data['indirizzo_residenza']),
                'telefono' => sanitize_text_field($data['telefono']),
                'email' => sanitize_email($data['email']),
                'file_name' => sanitize_file_name($data['file_name']),
                'file_path' => sanitize_text_field($data['file_path']),
                'file_size' => absint($data['file_size']),
                'ip_address' => sanitize_text_field($data['ip_address']),
                'user_agent' => sanitize_text_field($data['user_agent']),
                'submitted_at' => current_time('mysql'),
                'status' => 'pending',
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s')
        );
        
        if ($result === false) {
            return false;
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get all submissions with pagination
     */
    public function get_submissions($per_page = 20, $page = 1, $search = '') {
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        
        $where = '1=1';
        if (!empty($search)) {
            $search = $wpdb->esc_like($search);
            $where .= $wpdb->prepare(
                " AND (cognome LIKE %s OR nome LIKE %s OR codice_fiscale LIKE %s OR email LIKE %s)",
                "%$search%", "%$search%", "%$search%", "%$search%"
            );
        }
        
        $query = "SELECT * FROM {$this->table_name} WHERE $where ORDER BY submitted_at DESC LIMIT %d OFFSET %d";
        $results = $wpdb->get_results($wpdb->prepare($query, $per_page, $offset), ARRAY_A);
        
        return $results;
    }
    
    /**
     * Get total count of submissions
     */
    public function get_total_count($search = '') {
        global $wpdb;
        
        $where = '1=1';
        if (!empty($search)) {
            $search = $wpdb->esc_like($search);
            $where .= $wpdb->prepare(
                " AND (cognome LIKE %s OR nome LIKE %s OR codice_fiscale LIKE %s OR email LIKE %s)",
                "%$search%", "%$search%", "%$search%", "%$search%"
            );
        }
        
        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE $where";
        return $wpdb->get_var($query);
    }
    
    /**
     * Get single submission by ID
     */
    public function get_submission($id) {
        global $wpdb;
        
        $query = $wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id);
        return $wpdb->get_row($query, ARRAY_A);
    }
    
    /**
     * Delete submission by ID
     */
    public function delete_submission($id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Update submission notes
     */
    public function update_notes($id, $notes) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('notes' => sanitize_textarea_field($notes)),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Update submission status
     */
    public function update_status($id, $status) {
        global $wpdb;
        
        return $wpdb->update(
            $this->table_name,
            array('status' => sanitize_text_field($status)),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * Check if codice fiscale already exists
     */
    public function cf_exists($codice_fiscale) {
        global $wpdb;
        
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE codice_fiscale = %s",
            strtoupper($codice_fiscale)
        );
        
        return $wpdb->get_var($query) > 0;
    }
}
