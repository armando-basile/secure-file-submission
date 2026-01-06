/**
 * Secure File Submission - Admin JavaScript
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        // Toggle submission details
        $('.sfs-view-details').on('click', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('#sfs-details-' + id).slideToggle();
        });
        
        // Confirm deletion
        $('.sfs-delete-btn').on('click', function(e) {
            if (!confirm(sfsAdmin.confirmDelete)) {
                e.preventDefault();
                return false;
            }
        });
        
    });
    
})(jQuery);
