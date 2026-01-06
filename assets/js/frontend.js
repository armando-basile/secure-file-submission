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
        
        // Uppercase codice fiscale as user types
        $('#sfs_codice_fiscale').on('input', function() {
            this.value = this.value.toUpperCase();
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
            
            // Disable submit button
            submitBtn.prop('disabled', true).addClass('loading');
            
            // Show progress bar
            progressDiv.show();
            updateProgress(0);
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', 'sfs_submit');
            formData.append('sfs_nonce', sfsData.nonce);
            
            // Add reCAPTCHA token if configured
            if (sfsData.recaptchaSiteKey) {
                grecaptcha.ready(function() {
                    grecaptcha.execute(sfsData.recaptchaSiteKey, {action: 'submit'}).then(function(token) {
                        formData.append('recaptcha_token', token);
                        submitForm(formData);
                    });
                });
            } else {
                submitForm(formData);
            }
        });
        
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
                    }
                },
                error: function(xhr, status, error) {
                    showMessage('error', sfsData.messages.errorSending);
                    progressDiv.hide();
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
