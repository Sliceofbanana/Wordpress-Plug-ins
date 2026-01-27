jQuery(document).ready(function($) {
    
    // Handle review form submission
    $('#src-review-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('.src-submit-btn');
        var messageDiv = form.find('.src-message');
        var formData = new FormData(this);
        
        // Disable submit button
        submitBtn.prop('disabled', true).text('Submitting...');
        messageDiv.hide().removeClass('success error');
        
        $.ajax({
            url: srcAjax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    messageDiv.addClass('success').text(response.data).show();
                    form[0].reset();
                } else {
                    messageDiv.addClass('error').text(response.data).show();
                }
            },
            error: function() {
                messageDiv.addClass('error').text('An error occurred. Please try again.').show();
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Submit Review');
            }
        });
    });
    
});