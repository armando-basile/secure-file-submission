<?php
/**
 * Frontend Form Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class SFS_Frontend {
    
    public function __construct() {
        add_shortcode('secure_file_form', array($this, 'render_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        // Only load on pages with the shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'secure_file_form')) {
            wp_enqueue_style('sfs-frontend', SFS_PLUGIN_URL . 'assets/css/frontend.css', array(), SFS_VERSION);
            wp_enqueue_script('sfs-frontend', SFS_PLUGIN_URL . 'assets/js/frontend.js', array('jquery'), SFS_VERSION, true);
            
            // Add reCAPTCHA if configured
            $site_key = get_option('sfs_recaptcha_site_key');
            if (!empty($site_key)) {
                wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . $site_key, array(), null, true);
            }
            
            // Localize script
            wp_localize_script('sfs-frontend', 'sfsData', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sfs_submit_form'),
                'recaptchaSiteKey' => $site_key,
                'maxFileSize' => get_option('sfs_max_file_size', 524288000),
                'maxFileSizeMB' => round(get_option('sfs_max_file_size', 524288000) / 1024 / 1024),
                'messages' => array(
                    'fileTooLarge' => sprintf(__('Il file supera la dimensione massima di %s MB.', 'secure-file-submission'), round(get_option('sfs_max_file_size', 524288000) / 1024 / 1024)),
                    'invalidFileType' => __('Sono ammessi solo file ZIP.', 'secure-file-submission'),
                    'errorSending' => __('Errore durante l\'invio. Riprova più tardi.', 'secure-file-submission'),
                    'finalizing' => __('Finalizzazione in corso, attendere...', 'secure-file-submission'),
                )
            ));
        }
    }
    
    /**
     * Render the submission form
     */
    public function render_form($atts) {
        $atts = shortcode_atts(array(
            'title' => __('Invia Documentazione', 'secure-file-submission'),
        ), $atts);
        
        ob_start();
        ?>
        <div class="sfs-form-wrapper">
            <div class="sfs-form-container">
                <?php if (!empty($atts['title'])): ?>
                    <h2 class="sfs-form-title"><?php echo esc_html($atts['title']); ?></h2>
                <?php endif; ?>
                
                <div id="sfs-messages"></div>
                
                <form id="sfs-submission-form" enctype="multipart/form-data">
                    <?php wp_nonce_field('sfs_submit_form', 'sfs_nonce'); ?>
                    
                    <div class="sfs-form-row">
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_cognome"><?php esc_html_e('Cognome', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="text" id="sfs_cognome" name="cognome" required>
                        </div>
                        
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_nome"><?php esc_html_e('Nome', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="text" id="sfs_nome" name="nome" required>
                        </div>
                    </div>
                    
                    <div class="sfs-form-row">
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_data_nascita"><?php esc_html_e('Data di Nascita', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="date" id="sfs_data_nascita" name="data_nascita" required>
                        </div>
                        
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_comune_nascita"><?php esc_html_e('Comune di Nascita', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="text" id="sfs_comune_nascita" name="comune_nascita" required>
                        </div>
                    </div>
                    
                    <div class="sfs-form-group">
                        <label for="sfs_codice_fiscale"><?php esc_html_e('Codice Fiscale', 'secure-file-submission'); ?> <span class="required">*</span></label>
                        <input type="text" id="sfs_codice_fiscale" name="codice_fiscale" maxlength="16" pattern="[A-Za-z0-9]{16}" required style="text-transform: uppercase;">
                        <small class="sfs-help-text"><?php esc_html_e('Inserire 16 caratteri senza spazi', 'secure-file-submission'); ?></small>
                    </div>
                    
                    <div class="sfs-form-group">
                        <label for="sfs_comune_residenza"><?php esc_html_e('Comune di Residenza', 'secure-file-submission'); ?> <span class="required">*</span></label>
                        <input type="text" id="sfs_comune_residenza" name="comune_residenza" required>
                    </div>
                    
                    <div class="sfs-form-group">
                        <label for="sfs_indirizzo_residenza"><?php esc_html_e('Indirizzo di Residenza', 'secure-file-submission'); ?> <span class="required">*</span></label>
                        <input type="text" id="sfs_indirizzo_residenza" name="indirizzo_residenza" required>
                        <small class="sfs-help-text"><?php esc_html_e('Via, numero civico', 'secure-file-submission'); ?></small>
                    </div>
                    
                    <div class="sfs-form-row">
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_telefono"><?php esc_html_e('Telefono Cellulare', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="tel" id="sfs_telefono" name="telefono" pattern="[0-9\s\+\-\(\)]{10,20}" required>
                            <small class="sfs-help-text"><?php esc_html_e('Formato: con o senza spazi (es. 3331234567 o 333 123 4567)', 'secure-file-submission'); ?></small>
                        </div>
                        
                        <div class="sfs-form-group sfs-half">
                            <label for="sfs_email"><?php esc_html_e('Email', 'secure-file-submission'); ?> <span class="required">*</span></label>
                            <input type="email" id="sfs_email" name="email" required>
                        </div>
                    </div>
                    
                    <div class="sfs-form-group">
                        <label for="sfs_file"><?php esc_html_e('File ZIP', 'secure-file-submission'); ?> <span class="required">*</span></label>
                        <input type="file" id="sfs_file" name="file" accept=".zip" required>
                        <small class="sfs-help-text"><?php printf(esc_html__('File ZIP, dimensione massima: %s MB', 'secure-file-submission'), round(get_option('sfs_max_file_size', 524288000) / 1024 / 1024)); ?></small>
                        <div id="sfs-upload-progress" style="display:none;">
                            <div class="sfs-progress-bar">
                                <div class="sfs-progress-fill"></div>
                            </div>
                            <span class="sfs-progress-text">0%</span>
                        </div>
                    </div>
                    
                    <div class="sfs-form-group sfs-terms-group">
                        <label class="sfs-checkbox-label">
                            <input type="checkbox" id="sfs_terms" name="terms" required>
                            <span><?php esc_html_e('Accetto i termini e condizioni', 'secure-file-submission'); ?> <span class="required">*</span></span>
                        </label>
                        <small class="sfs-help-text sfs-terms-error" style="display:none; color: #dc3232;"><?php esc_html_e('Devi accettare i termini e condizioni per continuare.', 'secure-file-submission'); ?></small>
                    </div>
                    
                    <div class="sfs-form-group">
                        <button type="submit" id="sfs-submit-btn" class="sfs-submit-button">
                            <span class="sfs-btn-text"><?php esc_html_e('Invia Documentazione', 'secure-file-submission'); ?></span>
                            <span class="sfs-btn-loading" style="display:none;">
                                <span class="sfs-spinner"></span> <?php esc_html_e('Invio in corso...', 'secure-file-submission'); ?>
                            </span>
                        </button>
                    </div>
                    
                    <p class="sfs-required-note"><span class="required">*</span> <?php esc_html_e('Campi obbligatori', 'secure-file-submission'); ?></p>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
