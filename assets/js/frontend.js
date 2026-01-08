/**
 * Secure File Submission - Frontend JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        var form = $('#sfs-submission-form');
        var submitBtn = $('#sfs-submit-btn');
        var messagesDiv = $('#sfs-messages');
        var fileInput = $('#sfs_file');
        var progressDiv = $('#sfs-upload-progress');
        var progressBar = $('.sfs-progress-fill');
        var progressText = $('.sfs-progress-text');
        var termsCheckbox = $('#sfs_terms');
        var termsError = $('.sfs-terms-error');
        
        // Uppercase codice fiscale as user types
        $('#sfs_codice_fiscale').on('input', function() {
            this.value = this.value.toUpperCase();
        });
        
        // Hide terms error when checkbox is checked
        termsCheckbox.on('change', function() {
            if (this.checked) {
                termsError.hide();
            }
        });
        
        // File size validation on selection
        fileInput.on('change', function() {
            var file = this.files[0];
            if (file) {
                var maxSize = sfsData.maxFileSize;
                if (file.size > maxSize) {
                    showMessage('error', sfsData.messages.fileTooLarge);
                    this.value = '';
                    return false;
                }
                
                // Check file extension
                var fileName = file.name;
                var fileExt = fileName.split('.').pop().toLowerCase();
                if (fileExt !== 'zip') {
                    showMessage('error', sfsData.messages.invalidFileType);
                    this.value = '';
                    return false;
                }
            }
        });
        
        // Form submission
        form.on('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            messagesDiv.empty();
            termsError.hide();
            
            // Validate terms and conditions checkbox
            if (!termsCheckbox.prop('checked')) {
                termsError.show();
                $('html, body').animate({
                    scrollTop: termsCheckbox.offset().top - 100
                }, 500);
                return false;
            }
            
            // Disable submit button
            submitBtn.prop('disabled', true).addClass('loading');
            
            // Show progress bar
            progressDiv.show();
            updateProgress(0);
            
            // Get file for chunked upload
            var file = fileInput[0].files[0];
            if (!file) {
                showMessage('error', 'Nessun file selezionato.');
                submitBtn.prop('disabled', false).removeClass('loading');
                return;
            }
            
            // Prepare form data WITHOUT file (we'll send it separately)
            var formData = new FormData(this);
            formData.delete('file'); // Remove file from form data
            formData.append('action', 'sfs_submit');
            formData.append('sfs_nonce', sfsData.nonce);
            
            // Add reCAPTCHA token if configured
            if (sfsData.recaptchaSiteKey) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(sfsData.recaptchaSiteKey, {action: 'submit'}).then(function(token) {
                        formData.append('recaptcha_token', token);
                        uploadChunked(file, formData);
                    });
                });
            } else {
                uploadChunked(file, formData);
            }
        });
        
        function uploadChunked(file, formData) {
            var chunkSize = 5 * 1024 * 1024; // 5MB chunks
            var totalChunks = Math.ceil(file.size / chunkSize);
            var currentChunk = 0;
            var uploadId = Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            function uploadNextChunk() {
                if (currentChunk >= totalChunks) {
                    // All chunks uploaded, finalize
                    finalizeUpload(uploadId, formData);
                    return;
                }
                
                var start = currentChunk * chunkSize;
                var end = Math.min(start + chunkSize, file.size);
                var chunk = file.slice(start, end);
                
                var chunkFormData = new FormData();
                chunkFormData.append('action', 'sfs_upload_chunk');
                chunkFormData.append('chunk', chunk);
                chunkFormData.append('chunk_index', currentChunk);
                chunkFormData.append('total_chunks', totalChunks);
                chunkFormData.append('upload_id', uploadId);
                chunkFormData.append('file_name', file.name);
                chunkFormData.append('sfs_nonce', sfsData.nonce);
                
                $.ajax({
                    url: sfsData.ajaxurl,
                    type: 'POST',
                    data: chunkFormData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            currentChunk++;
                            var progress = Math.round((currentChunk / totalChunks) * 100);
                            updateProgress(progress);
                            uploadNextChunk();
                        } else {
                            showMessage('error', response.data.message);
                            progressDiv.hide();
                            submitBtn.prop('disabled', false).removeClass('loading');
                        }
                    },
                    error: function() {
                        showMessage('error', 'Errore durante l\'upload del file.');
                        progressDiv.hide();
                        submitBtn.prop('disabled', false).removeClass('loading');
                    }
                });
            }
            
            uploadNextChunk();
        }
        
        function finalizeUpload(uploadId, formData) {
            formData.append('upload_id', uploadId);
            
            submitForm(formData);
        }
        
        function submitForm(formData) {
            $.ajax({
                url: sfsData.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                xhr: function() {
                    var xhr = new window.XMLHttpRequest();
                    // Upload progress
                    xhr.upload.addEventListener("progress", function(evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            updateProgress(percentComplete);
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.data.message);
                        form[0].reset();
                        progressDiv.hide();
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: messagesDiv.offset().top - 100
                        }, 500);
                    } else {
                        showMessage('error', response.data.message);
                        progressDiv.hide();
                        
                        // Scroll to error message
                        $('html, body').animate({
                            scrollTop: messagesDiv.offset().top - 100
                        }, 500);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('error', sfsData.messages.errorSending);
                    progressDiv.hide();
                    
                    // Scroll to error message
                    $('html, body').animate({
                        scrollTop: messagesDiv.offset().top - 100
                    }, 500);
                    
                    console.error('AJAX Error:', error);
                },
                complete: function() {
                    submitBtn.prop('disabled', false).removeClass('loading');
                }
            });
        }
        
        function updateProgress(percent) {
            progressBar.css('width', percent + '%');
            progressText.text(percent + '%');
        }
        
        function showMessage(type, message) {
            var messageHtml = '<div class="sfs-message ' + type + '">' + message + '</div>';
            messagesDiv.html(messageHtml);
        }
    });
    
})(jQuery);
