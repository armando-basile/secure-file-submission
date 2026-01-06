<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Submissions Ricevute', 'secure-file-submission'); ?></h1>
    
    <?php if (isset($_GET['deleted'])): ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Submission eliminata con successo.', 'secure-file-submission'); ?></p>
        </div>
    <?php endif; ?>
    
    <form method="get" action="">
        <input type="hidden" name="page" value="sfs-submissions">
        <p class="search-box">
            <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Cerca per nome, CF, email...', 'secure-file-submission'); ?>">
            <input type="submit" class="button" value="<?php esc_attr_e('Cerca', 'secure-file-submission'); ?>">
            <?php if ($search): ?>
                <a href="<?php echo admin_url('admin.php?page=sfs-submissions'); ?>" class="button"><?php esc_html_e('Rimuovi filtro', 'secure-file-submission'); ?></a>
            <?php endif; ?>
        </p>
    </form>
    
    <div class="sfs-stats-boxes">
        <div class="sfs-stat-box">
            <h3><?php esc_html_e('Totale Submissions', 'secure-file-submission'); ?></h3>
            <p class="sfs-stat-number"><?php echo number_format($total_items); ?></p>
        </div>
        <div class="sfs-stat-box">
            <h3><?php esc_html_e('Spazio Utilizzato', 'secure-file-submission'); ?></h3>
            <p class="sfs-stat-number"><?php echo size_format($total_size); ?></p>
        </div>
        <div class="sfs-stat-box">
            <h3><?php esc_html_e('Spazio Libero', 'secure-file-submission'); ?></h3>
            <p class="sfs-stat-number"><?php echo size_format($free_space); ?></p>
        </div>
    </div>
    
    <?php if (empty($submissions)): ?>
        <p><?php esc_html_e('Nessuna submission trovata.', 'secure-file-submission'); ?></p>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th><?php esc_html_e('Nome Cognome', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('Codice Fiscale', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('Email', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('Telefono', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('File', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('Data Invio', 'secure-file-submission'); ?></th>
                    <th><?php esc_html_e('Azioni', 'secure-file-submission'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($submissions as $submission): ?>
                    <tr>
                        <td><?php echo esc_html($submission['id']); ?></td>
                        <td>
                            <strong><?php echo esc_html($submission['nome'] . ' ' . $submission['cognome']); ?></strong>
                            <div class="row-actions">
                                <span><a href="#" class="sfs-view-details" data-id="<?php echo esc_attr($submission['id']); ?>"><?php esc_html_e('Visualizza dettagli', 'secure-file-submission'); ?></a></span>
                            </div>
                            
                            <!-- Hidden details row -->
                            <div id="sfs-details-<?php echo esc_attr($submission['id']); ?>" class="sfs-submission-details" style="display:none;">
                                <table class="sfs-details-table">
                                    <tr>
                                        <th><?php esc_html_e('Data di Nascita:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html(date('d/m/Y', strtotime($submission['data_nascita']))); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Comune di Nascita:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html($submission['comune_nascita']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Comune di Residenza:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html($submission['comune_residenza']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('Indirizzo di Residenza:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html($submission['indirizzo_residenza']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('IP Address:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html($submission['ip_address']); ?></td>
                                    </tr>
                                    <tr>
                                        <th><?php esc_html_e('User Agent:', 'secure-file-submission'); ?></th>
                                        <td><?php echo esc_html($submission['user_agent']); ?></td>
                                    </tr>
                                </table>
                            </div>
                        </td>
                        <td><?php echo esc_html($submission['codice_fiscale']); ?></td>
                        <td><a href="mailto:<?php echo esc_attr($submission['email']); ?>"><?php echo esc_html($submission['email']); ?></a></td>
                        <td><?php echo esc_html($submission['telefono']); ?></td>
                        <td>
                            <strong><?php echo esc_html($submission['file_name']); ?></strong><br>
                            <small><?php echo size_format($submission['file_size']); ?></small>
                        </td>
                        <td><?php echo esc_html(date('d/m/Y H:i', strtotime($submission['submitted_at']))); ?></td>
                        <td>
                            <?php 
                            $file_handler = new SFS_File_Handler();
                            $download_url = $file_handler->get_download_url($submission['id']);
                            $delete_url = wp_nonce_url(
                                admin_url('admin-post.php?action=sfs_delete_submission&id=' . $submission['id']),
                                'sfs_delete_submission'
                            );
                            ?>
                            <a href="<?php echo esc_url($download_url); ?>" class="button button-small" target="_blank">
                                <span class="dashicons dashicons-download"></span> <?php esc_html_e('Download', 'secure-file-submission'); ?>
                            </a>
                            <a href="<?php echo esc_url($delete_url); ?>" class="button button-small sfs-delete-btn">
                                <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Elimina', 'secure-file-submission'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if ($total_pages > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => '&laquo;',
                        'next_text' => '&raquo;',
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    ?>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
