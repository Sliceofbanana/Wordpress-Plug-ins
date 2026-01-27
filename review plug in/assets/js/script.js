jQuery(document).ready(function($) {
    
    // Handle inline form submission
    $('#src-review-form').on('submit', function(e) {
        e.preventDefault();
        submitReviewForm($(this));
    });
    
    // Handle popup form submission
    $('#src-review-form-popup').on('submit', function(e) {
        e.preventDefault();
        submitReviewForm($(this));
    });
    
    // Function to submit review
    function submitReviewForm(form) {
        var submitBtn = form.find('.src-submit-btn');
        var messageDiv = form.find('.src-message');
        var formData = new FormData(form[0]);
        
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
                    
                    // Close popup if it's a popup form
                    if (form.attr('id') === 'src-review-form-popup') {
                        setTimeout(function() {
                            $('#src-review-popup').removeClass('active');
                        }, 2000);
                    }
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
    }
    
    // Open popup
    $('.src-open-popup-btn').on('click', function() {
        $('#src-review-popup').addClass('active');
    });
    
    // Close popup
    $('.src-popup-close').on('click', function() {
        $('#src-review-popup').removeClass('active');
    });
    
    // Close popup when clicking outside
    $('#src-review-popup').on('click', function(e) {
        if ($(e.target).is('#src-review-popup')) {
            $(this).removeClass('active');
        }
    });
    
    // Carousel navigation
    $('.src-carousel-prev').on('click', function() {
        var container = $(this).siblings('.src-reviews-wrapper');
        var scrollAmount = container.find('.src-review-card').outerWidth(true);
        container.animate({
            scrollLeft: container.scrollLeft() - scrollAmount
        }, 300);
    });
    
    $('.src-carousel-next').on('click', function() {
        var container = $(this).siblings('.src-reviews-wrapper');
        var scrollAmount = container.find('.src-review-card').outerWidth(true);
        container.animate({
            scrollLeft: container.scrollLeft() + scrollAmount
        }, 300);
    });
    
});