<?php
/**
 * AJAX Request Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_AJAX {
    
    public function __construct() {
        // Handle form submission
        add_action('wp_ajax_nopriv_sfs_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_sfs_submit', array($this, 'handle_submission'));
        
        // Handle chunked upload
        add_action('wp_ajax_nopriv_sfs_upload_chunk', array($this, 'handle_chunk_upload'));
        add_action('wp_ajax_sfs_upload_chunk', array($this, 'handle_chunk_upload'));
        
        // Handle file download
        add_action('wp_ajax_sfs_download', array($this, 'handle_download'));
        
        // Cleanup old chunks (run on init)
        add_action('init', array($this, 'cleanup_old_chunks'));
    }
    
    /**
     * Handle chunked file upload
     */
    public function handle_chunk_upload() {
        // Verify nonce
        if (!isset($_POST['sfs_nonce']) || !wp_verify_nonce($_POST['sfs_nonce'], 'sfs_submit_form')) {
            wp_send_json_error(array('message' => 'Verifica di sicurezza fallita.'));
        }
        
        $upload_id = sanitize_text_field($_POST['upload_id']);
        $chunk_index = intval($_POST['chunk_index']);
        $total_chunks = intval($_POST['total_chunks']);
        $file_name = sanitize_file_name($_POST['file_name']);
        
        // Create temporary directory for chunks
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sfs-temp-uploads';
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        // Save chunk
        $chunk_dir = $temp_dir . '/' . $upload_id;
        if (!file_exists($chunk_dir)) {
            wp_mkdir_p($chunk_dir);
        }
        
        // Save original filename on first chunk
        if ($chunk_index === 0) {
            file_put_contents($chunk_dir . '/original_filename.txt', $file_name);
        }
        
        $chunk_file = $chunk_dir . '/chunk_' . $chunk_index;
        
        if (!isset($_FILES['chunk'])) {
            wp_send_json_error(array('message' => 'Chunk non ricevuto.'));
        }
        
        if (move_uploaded_file($_FILES['chunk']['tmp_name'], $chunk_file)) {
            wp_send_json_success(array(
                'message' => 'Chunk caricato',
                'chunk_index' => $chunk_index,
                'total_chunks' => $total_chunks
            ));
        } else {
            wp_send_json_error(array('message' => 'Errore nel salvare il chunk.'));
        }
    }
    
    /**
     * Handle form submission via AJAX
     */
    public function handle_submission() {
        // Verify nonce
        if (!isset($_POST['sfs_nonce']) || !wp_verify_nonce($_POST['sfs_nonce'], 'sfs_submit_form')) {
            wp_send_json_error(array('message' => __('Verifica di sicurezza fallita. Ricarica la pagina e riprova.', 'secure-file-submission')));
        }
        
        // Verify reCAPTCHA if configured
        $recaptcha_secret = get_option('sfs_recaptcha_secret_key');
        $recaptcha_site_key = get_option('sfs_recaptcha_site_key');
        
        if (!empty($recaptcha_secret) && !empty($recaptcha_site_key)) {
            if (!isset($_POST['recaptcha_token']) || empty($_POST['recaptcha_token'])) {
                // Log for debugging
                error_log('SFS reCAPTCHA: Token mancante. Site Key configurata: ' . substr($recaptcha_site_key, 0, 20) . '...');
                wp_send_json_error(array(
                    'message' => __('Verifica reCAPTCHA mancante. Assicurati che JavaScript sia abilitato e che il sito possa caricare script esterni (disabilita eventuali ad-blocker).', 'secure-file-submission')
                ));
            }
            
            // Log token received
            error_log('SFS reCAPTCHA: Token ricevuto: ' . substr($_POST['recaptcha_token'], 0, 50) . '...');
            
            $recaptcha_response = $this->verify_recaptcha($_POST['recaptcha_token'], $recaptcha_secret);
            
            // Log response
            error_log('SFS reCAPTCHA: Risposta Google: ' . json_encode($recaptcha_response));
            
            if (!$recaptcha_response['success']) {
                $error_msg = __('Verifica reCAPTCHA fallita.', 'secure-file-submission');
                if (isset($recaptcha_response['error-codes'])) {
                    $error_msg .= ' ' . __('Codici errore:', 'secure-file-submission') . ' ' . implode(', ', $recaptcha_response['error-codes']);
                }
                wp_send_json_error(array('message' => $error_msg));
            }
            
            // Check reCAPTCHA score (v3)
            if (isset($recaptcha_response['score']) && $recaptcha_response['score'] < 0.5) {
                error_log('SFS reCAPTCHA: Score troppo basso: ' . $recaptcha_response['score']);
                wp_send_json_error(array('message' => __('Score reCAPTCHA troppo basso. Contatta l\'amministratore se il problema persiste.', 'secure-file-submission')));
            }
            
            error_log('SFS reCAPTCHA: Verifica completata con successo. Score: ' . ($recaptcha_response['score'] ?? 'N/A'));
        }
        
        // Validate all required fields
        $required_fields = array(
            'cognome' => __('Cognome', 'secure-file-submission'),
            'nome' => __('Nome', 'secure-file-submission'),
            'data_nascita' => __('Data di Nascita', 'secure-file-submission'),
            'comune_nascita' => __('Comune di Nascita', 'secure-file-submission'),
            'codice_fiscale' => __('Codice Fiscale', 'secure-file-submission'),
            'comune_residenza' => __('Comune di Residenza', 'secure-file-submission'),
            'indirizzo_residenza' => __('Indirizzo di Residenza', 'secure-file-submission'),
            'telefono' => __('Telefono Cellulare', 'secure-file-submission'),
            'email' => __('Email', 'secure-file-submission'),
        );
        
        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                /* translators: %s: field label */
                wp_send_json_error(array('message' => sprintf(__('Il campo \'%s\' è obbligatorio.', 'secure-file-submission'), $label)));
            }
        }
        
        // Validate terms and conditions acceptance
        if (empty($_POST['terms']) || $_POST['terms'] !== 'on') {
            wp_send_json_error(array('message' => __('Devi accettare i termini e condizioni per continuare.', 'secure-file-submission')));
        }
        
        // Validate Codice Fiscale
        $cf_validation = SFS_Codice_Fiscale::validate_detailed($_POST['codice_fiscale']);
        if (!$cf_validation['valid']) {
            wp_send_json_error(array('message' => $cf_validation['message']));
        }
        
        // Check if Codice Fiscale already exists
        $db = new SFS_Database();
        if ($db->cf_exists($_POST['codice_fiscale'])) {
            wp_send_json_error(array('message' => __('Questo Codice Fiscale è già stato utilizzato per una submission precedente.', 'secure-file-submission')));
        }
        
        // Validate Email
        $email_validation = SFS_Email_Validator::validate_detailed($_POST['email']);
        if (!$email_validation['valid']) {
            wp_send_json_error(array('message' => $email_validation['message']));
        }
        
        // Check for disposable email
        if (SFS_Email_Validator::is_disposable($_POST['email'])) {
            wp_send_json_error(array('message' => __('Gli indirizzi email temporanei non sono consentiti.', 'secure-file-submission')));
        }
        
        // Handle file upload (chunked or direct)
        $file_handler = new SFS_File_Handler();
        
        if (isset($_POST['upload_id'])) {
            // Chunked upload - reassemble chunks
            $upload_id = sanitize_text_field($_POST['upload_id']);
            $upload_result = $this->reassemble_chunks($upload_id, $_POST['codice_fiscale']);
        } else {
            // Direct upload (fallback for small files or old browsers)
            if (!isset($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
                wp_send_json_error(array('message' => __('Il file ZIP è obbligatorio.', 'secure-file-submission')));
            }
            
            $upload_result = $file_handler->handle_upload($_FILES['file'], $_POST['codice_fiscale']);
        }
        
        if (!$upload_result['success']) {
            wp_send_json_error(array('message' => $upload_result['message']));
        }
        
        // Normalize phone number (remove spaces and keep only digits and +)
        $telefono = preg_replace('/[^0-9+]/', '', $_POST['telefono']);
        
        // Prepare data for database
        $submission_data = array(
            'cognome' => $_POST['cognome'],
            'nome' => $_POST['nome'],
            'data_nascita' => $_POST['data_nascita'],
            'comune_nascita' => $_POST['comune_nascita'],
            'codice_fiscale' => $_POST['codice_fiscale'],
            'comune_residenza' => $_POST['comune_residenza'],
            'indirizzo_residenza' => $_POST['indirizzo_residenza'],
            'telefono' => $telefono,
            'email' => $_POST['email'],
            'file_name' => $upload_result['file_name'],
            'file_path' => $upload_result['file_path'],
            'file_size' => $upload_result['file_size'],
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
        );
        
        // Insert into database
        $submission_id = $db->insert_submission($submission_data);
        
        if (!$submission_id) {
            // If database insert fails, delete the uploaded file
            $file_handler->delete_file($upload_result['file_path']);
            wp_send_json_error(array('message' => __('Errore durante il salvataggio dei dati. Riprova più tardi.', 'secure-file-submission')));
        }
        
        // Send notification emails
        $this->send_admin_notification($submission_id, $submission_data);
        $this->send_user_confirmation($submission_data);
        
        wp_send_json_success(array(
            'message' => __('Documentazione inviata con successo! Riceverai una email di conferma a breve.', 'secure-file-submission'),
            'submission_id' => $submission_id
        ));
    }
    
    /**
     * Verify reCAPTCHA token
     */
    private function verify_recaptcha($token, $secret) {
        $response = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', array(
            'body' => array(
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $this->get_client_ip()
            )
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false);
        }
        
        $body = wp_remote_retrieve_body($response);
        return json_decode($body, true);
    }
    
    /**
     * Send notification email to admin
     */
    private function send_admin_notification($submission_id, $data) {
        $admin_email = get_option('sfs_admin_email', get_option('admin_email'));
        $subject = get_option('sfs_email_subject_admin', __('Nuova Submission File Ricevuta', 'secure-file-submission'));
        
        $file_handler = new SFS_File_Handler();
        $download_url = $file_handler->get_download_url($submission_id);
        
        $message = __('È stata ricevuta una nuova submission di documentazione.', 'secure-file-submission') . "\n\n";
        $message .= __('DATI ANAGRAFICI:', 'secure-file-submission') . "\n";
        $message .= "==================\n";
        $message .= __('Cognome e Nome:', 'secure-file-submission') . " {$data['cognome']} {$data['nome']}\n";
        $message .= __('Data di Nascita:', 'secure-file-submission') . " {$data['data_nascita']}\n";
        $message .= __('Comune di Nascita:', 'secure-file-submission') . " {$data['comune_nascita']}\n";
        $message .= __('Codice Fiscale:', 'secure-file-submission') . " {$data['codice_fiscale']}\n";
        $message .= __('Comune di Residenza:', 'secure-file-submission') . " {$data['comune_residenza']}\n";
        $message .= __('Indirizzo di Residenza:', 'secure-file-submission') . " {$data['indirizzo_residenza']}\n";
        $message .= __('Telefono:', 'secure-file-submission') . " {$data['telefono']}\n";
        $message .= __('Email:', 'secure-file-submission') . " {$data['email']}\n\n";
        
        $message .= __('FILE:', 'secure-file-submission') . "\n";
        $message .= "==================\n";
        $message .= __('Nome File:', 'secure-file-submission') . " {$data['file_name']}\n";
        $message .= __('Dimensione:', 'secure-file-submission') . " " . size_format($data['file_size']) . "\n\n";
        
        $message .= __('DOWNLOAD:', 'secure-file-submission') . "\n";
        $message .= "==================\n";
        $message .= __('Per scaricare il file, effettua il login su WordPress e clicca sul link seguente:', 'secure-file-submission') . "\n";
        $message .= $download_url . "\n\n";
        
        $message .= __('Oppure accedi alla sezione \'File Submissions\' nel pannello amministrativo.', 'secure-file-submission') . "\n\n";
        
        $message .= __('INFO TECNICHE:', 'secure-file-submission') . "\n";
        $message .= "==================\n";
        $message .= __('IP Address:', 'secure-file-submission') . " {$data['ip_address']}\n";
        $message .= __('Data Invio:', 'secure-file-submission') . " " . current_time('d/m/Y H:i:s') . "\n";
        $message .= __('Submission ID:', 'secure-file-submission') . " {$submission_id}\n";
        
        // Set Reply-To header if configured
        $reply_to = get_option('sfs_reply_to_email');
        $headers = array();
        if (!empty($reply_to)) {
            $headers[] = 'Reply-To: ' . $reply_to;
        }
        
        wp_mail($admin_email, $subject, $message, $headers);
    }
    
    /**
     * Send confirmation email to user
     */
    private function send_user_confirmation($data) {
        $subject = get_option('sfs_email_subject_user', __('Conferma Ricezione Richiesta', 'secure-file-submission'));
        
        $message = sprintf(__('Gentile %s %s,', 'secure-file-submission'), $data['nome'], $data['cognome']) . "\n\n";
        $message .= __('La tua documentazione è stata ricevuta con successo.', 'secure-file-submission') . "\n\n";
        $message .= __('Riepilogo:', 'secure-file-submission') . "\n";
        $message .= "- " . __('Codice Fiscale:', 'secure-file-submission') . " {$data['codice_fiscale']}\n";
        $message .= "- " . __('File:', 'secure-file-submission') . " {$data['file_name']}\n";
        $message .= "- " . __('Data invio:', 'secure-file-submission') . " " . current_time('d/m/Y H:i:s') . "\n\n";
        $message .= __('Ti contatteremo al più presto per le successive comunicazioni.', 'secure-file-submission') . "\n\n";
        $message .= __('Per qualsiasi informazione, rispondi a questa email.', 'secure-file-submission') . "\n\n";
        $message .= __('Cordiali saluti', 'secure-file-submission');
        
        wp_mail($data['email'], $subject, $message);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Handle file download
     */
    public function handle_download() {
        if (!isset($_GET['id'])) {
            wp_die(__('ID submission mancante.', 'secure-file-submission'));
        }
        
        $submission_id = absint($_GET['id']);
        $file_handler = new SFS_File_Handler();
        $file_handler->serve_download($submission_id);
    }
    
    /**
     * Reassemble uploaded chunks into final file
     */
    private function reassemble_chunks($upload_id, $codice_fiscale) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sfs-temp-uploads';
        $chunk_dir = $temp_dir . '/' . $upload_id;
        
        if (!file_exists($chunk_dir)) {
            return array(
                'success' => false,
                'message' => __('File temporanei non trovati. Upload ID: ', 'secure-file-submission') . $upload_id
            );
        }
        
        // Get original filename
        $original_filename_file = $chunk_dir . '/original_filename.txt';
        if (!file_exists($original_filename_file)) {
            return array(
                'success' => false,
                'message' => __('Nome file originale non trovato.', 'secure-file-submission')
            );
        }
        $original_filename = file_get_contents($original_filename_file);
        
        // Get all chunks and sort them
        $chunks = glob($chunk_dir . '/chunk_*');
        if (empty($chunks)) {
            return array(
                'success' => false,
                'message' => __('Nessun chunk trovato nella directory.', 'secure-file-submission')
            );
        }
        
        // Sort chunks by index
        usort($chunks, function($a, $b) use ($chunk_dir) {
            $a_index = intval(str_replace($chunk_dir . '/chunk_', '', $a));
            $b_index = intval(str_replace($chunk_dir . '/chunk_', '', $b));
            return $a_index - $b_index;
        });
        
        // Create final file in temp location
        $temp_file = $temp_dir . '/assembled_' . $upload_id . '.zip';
        $final_handle = fopen($temp_file, 'wb');
        
        if (!$final_handle) {
            return array(
                'success' => false,
                'message' => __('Impossibile creare il file finale.', 'secure-file-submission')
            );
        }
        
        // Concatenate all chunks
        foreach ($chunks as $chunk_file) {
            $chunk_handle = fopen($chunk_file, 'rb');
            if ($chunk_handle) {
                while (!feof($chunk_handle)) {
                    fwrite($final_handle, fread($chunk_handle, 8192));
                }
                fclose($chunk_handle);
                unlink($chunk_file); // Delete chunk after processing
            }
        }
        
        fclose($final_handle);
        
        // Delete original filename file
        unlink($original_filename_file);
        
        // Remove chunk directory
        rmdir($chunk_dir);
        
        // Create a fake $_FILES array for the file handler
        $file_size = filesize($temp_file);
        $fake_file = array(
            'name' => $original_filename,
            'type' => 'application/zip',
            'tmp_name' => $temp_file,
            'error' => 0,
            'size' => $file_size,
            'chunked' => true  // Flag to indicate this is a chunked upload
        );
        
        // Use existing file handler to process the reassembled file
        $file_handler = new SFS_File_Handler();
        $result = $file_handler->handle_upload($fake_file, $codice_fiscale);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        return $result;
    }
    
    /**
     * Cleanup old temporary chunks (older than 24 hours)
     */
    public function cleanup_old_chunks() {
        // Run cleanup only once per day
        $last_cleanup = get_transient('sfs_last_chunk_cleanup');
        if ($last_cleanup) {
            return;
        }
        
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/sfs-temp-uploads';
        
        if (!file_exists($temp_dir)) {
            return;
        }
        
        $directories = glob($temp_dir . '/*', GLOB_ONLYDIR);
        $current_time = time();
        
        foreach ($directories as $dir) {
            // Delete directories older than 24 hours
            if (($current_time - filemtime($dir)) > 86400) {
                $this->delete_directory($dir);
            }
        }
        
        // Also delete assembled temp files
        $temp_files = glob($temp_dir . '/assembled_*.zip');
        foreach ($temp_files as $file) {
            if (($current_time - filemtime($file)) > 86400) {
                unlink($file);
            }
        }
        
        // Set transient for 24 hours
        set_transient('sfs_last_chunk_cleanup', true, DAY_IN_SECONDS);
    }
    
    /**
     * Recursively delete a directory
     */
    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->delete_directory($path) : unlink($path);
        }
        
        rmdir($dir);
    }
}
