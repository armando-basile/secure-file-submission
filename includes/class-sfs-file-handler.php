<?php
/**
 * File Upload and Storage Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_File_Handler {
    
    private $upload_dir;
    
    public function __construct() {
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/secure-submissions';
    }
    
    /**
     * Check if total archive size would exceed limit
     * 
     * @param int $new_file_size Size of file to be uploaded in bytes
     * @return array Array with 'success' boolean and 'message' string
     */
    public function check_archive_size($new_file_size) {
        $max_archive_size_mb = get_option('sfs_max_archive_size', 2048); // 2GB default
        $max_archive_size_bytes = $max_archive_size_mb * 1024 * 1024;
        
        // Get current total size
        $current_total_size = $this->get_total_uploads_size();
        
        // Calculate what would be the new total
        $new_total_size = $current_total_size + $new_file_size;
        
        $current_size_mb = round($current_total_size / 1024 / 1024, 2);
        $new_file_mb = round($new_file_size / 1024 / 1024, 2);
        $new_total_mb = round($new_total_size / 1024 / 1024, 2);
        
        // Check if we would exceed the limit
        if ($new_total_size > $max_archive_size_bytes) {
            // Send alert email to admin
            $this->send_archive_size_alert($current_size_mb, $new_file_mb, $max_archive_size_mb);
            
            return array(
                'success' => false,
                'message' => __('Spazio archivio esaurito. Contattare l\'amministratore per liberare spazio.', 'secure-file-submission'),
                'current_size_mb' => $current_size_mb,
                'new_file_mb' => $new_file_mb,
                'max_size_mb' => $max_archive_size_mb
            );
        }
        
        return array(
            'success' => true,
            'message' => 'Spazio archivio sufficiente.',
            'current_size_mb' => $current_size_mb,
            'new_file_mb' => $new_file_mb,
            'new_total_mb' => $new_total_mb,
            'max_size_mb' => $max_archive_size_mb
        );
    }
    
    /**
     * Send alert email to admin about archive size limit
     */
    private function send_archive_size_alert($current_mb, $new_file_mb, $max_mb) {
        $admin_email = get_option('sfs_admin_email', get_option('admin_email'));
        
        $subject = '[ALERT] Limite Archivio Raggiunto - Secure File Submission';
        
        $message = "ATTENZIONE: Il limite di dimensione dell'archivio è stato raggiunto.\n\n";
        $message .= "Dimensione attuale archivio: {$current_mb} MB\n";
        $message .= "Dimensione file tentato: {$new_file_mb} MB\n";
        $message .= "Limite massimo configurato: {$max_mb} MB\n\n";
        $message .= "Un utente ha tentato di caricare un file ma l'operazione è stata bloccata perché l'archivio è pieno.\n\n";
        $message .= "Azione richiesta: Eliminare vecchi file dalla sezione 'Invii File' per liberare spazio.\n\n";
        $message .= "Percorso: {$this->upload_dir}";
        
        // Set Reply-To header if configured
        $reply_to = get_option('sfs_reply_to_email');
        $headers = array();
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Handle file upload
     * 
     * @param array $file $_FILES array element
     * @param string $codice_fiscale User's codice fiscale for filename
     * @return array Array with 'success' boolean, 'file_path', 'file_name', and 'message'
     */
    public function handle_upload($file, $codice_fiscale) {
        // Validate file was uploaded
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return array(
                'success' => false,
                'message' => 'Nessun file caricato o errore durante l\'upload.'
            );
        }
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return array(
                'success' => false,
                'message' => $this->get_upload_error_message($file['error'])
            );
        }
        
        // Validate file extension
        $allowed_extensions = array('zip');
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            return array(
                'success' => false,
                'message' => 'Sono ammessi solo file ZIP.'
            );
        }
        
        // Validate MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowed_mime_types = array(
            'application/zip',
            'application/x-zip-compressed',
            'multipart/x-zip'
        );
        
        if (!in_array($mime_type, $allowed_mime_types)) {
            return array(
                'success' => false,
                'message' => 'Il file non è un archivio ZIP valido.'
            );
        }
        
        // Check file size
        $max_size = get_option('sfs_max_file_size', 524288000); // 500MB default
        
        if ($file['size'] > $max_size) {
            $max_size_mb = round($max_size / 1024 / 1024);
            return array(
                'success' => false,
                'message' => "Il file supera la dimensione massima consentita di {$max_size_mb} MB."
            );
        }
        
        // Check archive size limit
        $space_check = $this->check_archive_size($file['size']);
        if (!$space_check['success']) {
            return $space_check;
        }
        
        // Generate unique filename
        $timestamp = date('YmdHis');
        $safe_cf = sanitize_file_name($codice_fiscale);
        $original_name = sanitize_file_name(pathinfo($file['name'], PATHINFO_FILENAME));
        $new_filename = "{$timestamp}_{$safe_cf}_{$original_name}.zip";
        
        // Full path
        $destination = $this->upload_dir . '/' . $new_filename;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return array(
                'success' => false,
                'message' => 'Errore durante il salvataggio del file sul server.'
            );
        }
        
        // Set proper permissions
        chmod($destination, 0644);
        
        return array(
            'success' => true,
            'file_path' => $destination,
            'file_name' => $new_filename,
            'file_size' => $file['size'],
            'message' => 'File caricato con successo.'
        );
    }
    
    /**
     * Get human-readable upload error message
     */
    private function get_upload_error_message($error_code) {
        $errors = array(
            UPLOAD_ERR_INI_SIZE => 'Il file supera la dimensione massima consentita dalla configurazione del server.',
            UPLOAD_ERR_FORM_SIZE => 'Il file supera la dimensione massima consentita.',
            UPLOAD_ERR_PARTIAL => 'Il file è stato caricato solo parzialmente.',
            UPLOAD_ERR_NO_FILE => 'Nessun file è stato caricato.',
            UPLOAD_ERR_NO_TMP_DIR => 'Cartella temporanea mancante sul server.',
            UPLOAD_ERR_CANT_WRITE => 'Impossibile scrivere il file su disco.',
            UPLOAD_ERR_EXTENSION => 'Upload bloccato da un\'estensione PHP.',
        );
        
        return isset($errors[$error_code]) ? $errors[$error_code] : 'Errore sconosciuto durante l\'upload.';
    }
    
    /**
     * Delete file from server
     * 
     * @param string $file_path Full path to file
     * @return bool True if deleted, false otherwise
     */
    public function delete_file($file_path) {
        if (file_exists($file_path)) {
            return unlink($file_path);
        }
        return false;
    }
    
    /**
     * Get file download URL (protected by authentication)
     * 
     * @param int $submission_id Submission ID
     * @return string Download URL
     */
    public function get_download_url($submission_id) {
        return add_query_arg(
            array(
                'action' => 'sfs_download',
                'id' => $submission_id,
                'nonce' => wp_create_nonce('sfs_download_' . $submission_id)
            ),
            admin_url('admin-ajax.php')
        );
    }
    
    /**
     * Serve file download (with authentication check)
     */
    public function serve_download($submission_id) {
        // Verify nonce
        if (!isset($_GET['nonce']) || !wp_verify_nonce($_GET['nonce'], 'sfs_download_' . $submission_id)) {
            wp_die('Link non valido o scaduto.', 'Errore', array('response' => 403));
        }
        
        // Check user capabilities
        if (!current_user_can('manage_file_submissions') && !current_user_can('manage_options')) {
            wp_die('Non hai i permessi per scaricare questo file.', 'Accesso Negato', array('response' => 403));
        }
        
        // Get submission
        $db = new SFS_Database();
        $submission = $db->get_submission($submission_id);
        
        if (!$submission) {
            wp_die('File non trovato.', 'Errore 404', array('response' => 404));
        }
        
        $file_path = $submission['file_path'];
        
        if (!file_exists($file_path)) {
            wp_die('Il file non esiste più sul server.', 'Errore 404', array('response' => 404));
        }
        
        // Clean ALL output buffers to prevent any output before headers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Clear any previous output
        if (headers_sent()) {
            die('Headers already sent. Cannot download file.');
        }
        
        // Serve file
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Content-Length: ' . filesize($file_path));
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Read and output file in chunks for large files
        $handle = fopen($file_path, 'rb');
        if ($handle === false) {
            die('Cannot open file.');
        }
        
        while (!feof($handle)) {
            echo fread($handle, 8192);
            flush();
        }
        
        fclose($handle);
        exit;
    }
    
    /**
     * Get total size of all uploaded files
     * 
     * @return int Total size in bytes
     */
    public function get_total_uploads_size() {
        $total_size = 0;
        
        if (is_dir($this->upload_dir)) {
            $files = glob($this->upload_dir . '/*.zip');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $total_size += filesize($file);
                }
            }
        }
        
        return $total_size;
    }
}
