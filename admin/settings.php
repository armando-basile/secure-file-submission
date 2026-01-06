<div class="wrap">
    <h1><?php esc_html_e('Impostazioni Secure File Submission', 'secure-file-submission'); ?></h1>
    
    <?php settings_errors(); ?>
    
    <div class="sfs-settings-wrapper">
        <form method="post" action="options.php">
            <?php settings_fields('sfs_settings'); ?>
            
            <h2><?php esc_html_e('Impostazioni File Upload', 'secure-file-submission'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sfs_max_file_size">Dimensione Massima File (MB)</label>
                    </th>
                    <td>
                        <input type="number" name="sfs_max_file_size" id="sfs_max_file_size" 
                               value="<?php echo esc_attr(get_option('sfs_max_file_size', 524288000) / 1024 / 1024); ?>" 
                               class="regular-text" min="1" max="2048" required>
                        <p class="description">Dimensione massima del file ZIP (in MB). Default: 500 MB</p>
                        <p class="description"><strong>Nota:</strong> Assicurati che i limiti PHP sul server siano configurati di conseguenza (upload_max_filesize, post_max_size).</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sfs_min_free_space">Spazio Minimo Libero (MB)</label>
                    </th>
                    <td>
                        <input type="number" name="sfs_min_free_space" id="sfs_min_free_space" 
                               value="<?php echo esc_attr(get_option('sfs_min_free_space', 2048)); ?>" 
                               class="regular-text" min="100" max="10000" required>
                        <p class="description">Spazio minimo che deve rimanere libero sul server dopo l'upload (in MB). Default: 2048 MB (2 GB)</p>
                        <p class="description">Se lo spazio disponibile scende sotto questa soglia, l'upload viene bloccato e l'admin riceve una email di allerta.</p>
                    </td>
                </tr>
            </table>
            
            <h2>Impostazioni Google reCAPTCHA v3</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sfs_recaptcha_site_key">Site Key</label>
                    </th>
                    <td>
                        <input type="text" name="sfs_recaptcha_site_key" id="sfs_recaptcha_site_key" 
                               value="<?php echo esc_attr(get_option('sfs_recaptcha_site_key')); ?>" 
                               class="regular-text">
                        <p class="description">Inserisci la Site Key di reCAPTCHA v3. <a href="https://www.google.com/recaptcha/admin" target="_blank">Ottieni le chiavi qui</a></p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sfs_recaptcha_secret_key">Secret Key</label>
                    </th>
                    <td>
                        <input type="text" name="sfs_recaptcha_secret_key" id="sfs_recaptcha_secret_key" 
                               value="<?php echo esc_attr(get_option('sfs_recaptcha_secret_key')); ?>" 
                               class="regular-text">
                        <p class="description">Inserisci la Secret Key di reCAPTCHA v3.</p>
                    </td>
                </tr>
            </table>
            
            <h2>Impostazioni Email</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="sfs_admin_email">Email Amministratore</label>
                    </th>
                    <td>
                        <input type="email" name="sfs_admin_email" id="sfs_admin_email" 
                               value="<?php echo esc_attr(get_option('sfs_admin_email', get_option('admin_email'))); ?>" 
                               class="regular-text" required>
                        <p class="description">Email a cui vengono inviate le notifiche di nuove submissions.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sfs_email_subject_admin">Oggetto Email Admin</label>
                    </th>
                    <td>
                        <input type="text" name="sfs_email_subject_admin" id="sfs_email_subject_admin" 
                               value="<?php echo esc_attr(get_option('sfs_email_subject_admin', 'Nuova Submission File Ricevuta')); ?>" 
                               class="regular-text" required>
                        <p class="description">Oggetto dell'email inviata all'amministratore.</p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="sfs_email_subject_user">Oggetto Email Utente</label>
                    </th>
                    <td>
                        <input type="text" name="sfs_email_subject_user" id="sfs_email_subject_user" 
                               value="<?php echo esc_attr(get_option('sfs_email_subject_user', 'Conferma Ricezione Richiesta')); ?>" 
                               class="regular-text" required>
                        <p class="description">Oggetto dell'email di conferma inviata all'utente.</p>
                    </td>
                </tr>
            </table>
            
            <?php submit_button('Salva Impostazioni'); ?>
        </form>
        
        <hr>
        
        <h2>Informazioni Sistema</h2>
        <table class="form-table">
            <tr>
                <th>Spazio Totale Utilizzato:</th>
                <td><strong><?php echo size_format($total_size); ?></strong></td>
            </tr>
            <tr>
                <th>Spazio Libero su Server:</th>
                <td><strong><?php echo size_format($free_space); ?></strong></td>
            </tr>
            <tr>
                <th>Directory Upload:</th>
                <td><code><?php echo esc_html($secure_dir); ?></code></td>
            </tr>
            <tr>
                <th>Versione Plugin:</th>
                <td><?php echo SFS_VERSION; ?></td>
            </tr>
        </table>
        
        <hr>
        
        <h2>Shortcode</h2>
        <p>Per visualizzare il form di submission in una pagina, usa il seguente shortcode:</p>
        <code>[secure_file_form]</code>
        <p>Puoi anche personalizzare il titolo:</p>
        <code>[secure_file_form title="Il tuo titolo personalizzato"]</code>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Convert MB to bytes when saving
    $('form').on('submit', function() {
        var maxSizeMB = $('#sfs_max_file_size').val();
        var maxSizeBytes = maxSizeMB * 1024 * 1024;
        $('#sfs_max_file_size').val(maxSizeBytes);
    });
});
</script>
