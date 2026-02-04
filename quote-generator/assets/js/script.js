(function($) {
    'use strict';
    
    let cart = [];
    let cartItemCounter = 0;
    
    $(document).ready(function() {
        
        // Initialize sortable for cart items
        $('#eqg-cart-items').sortable({
            handle: '.eqg-cart-item-drag',
            placeholder: 'eqg-cart-placeholder',
            axis: 'y',
            cursor: 'move'
        });
        
        // Product search filter
        $('#eqg-product-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            $('.eqg-product-card').each(function() {
                const productName = $(this).data('name').toLowerCase();
                if (productName.includes(searchTerm)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
            
            // Show/hide category headers
            $('.eqg-category-group').each(function() {
                const visibleProducts = $(this).find('.eqg-product-card:visible').length;
                if (visibleProducts > 0) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
        
        // Add product to cart
        $(document).on('click', '.eqg-add-product-btn', function(e) {
            e.preventDefault();
            const $card = $(this).closest('.eqg-product-card');
            addProductToCart($card);
        });
        
        // Drag and drop functionality
        $('.eqg-product-card').draggable({
            helper: 'clone',
            cursor: 'move',
            revert: 'invalid',
            start: function() {
                $(this).addClass('dragging');
            },
            stop: function() {
                $(this).removeClass('dragging');
            }
        });
        
        $('#eqg-cart-items').droppable({
            accept: '.eqg-product-card',
            drop: function(event, ui) {
                const $card = ui.draggable;
                addProductToCart($card);
            }
        });
        
        // Quantity controls
        $(document).on('click', '.eqg-qty-btn', function() {
            const $item = $(this).closest('.eqg-cart-item');
            const itemId = $item.data('item-id');
            const $input = $item.find('.eqg-qty-input');
            let qty = parseInt($input.val()) || 1;
            
            if ($(this).hasClass('qty-minus')) {
                qty = Math.max(1, qty - 1);
            } else {
                qty = qty + 1;
            }
            
            $input.val(qty);
            updateCartItem(itemId, qty);
        });
        
        // Manual quantity input
        $(document).on('change', '.eqg-qty-input', function() {
            const $item = $(this).closest('.eqg-cart-item');
            const itemId = $item.data('item-id');
            let qty = parseInt($(this).val()) || 1;
            qty = Math.max(1, qty);
            $(this).val(qty);
            updateCartItem(itemId, qty);
        });
        
        // Remove item from cart
        $(document).on('click', '.eqg-cart-item-remove', function() {
            const $item = $(this).closest('.eqg-cart-item');
            const itemId = $item.data('item-id');
            removeCartItem(itemId);
        });
        
        // Request custom product modal
        $(document).on('click', '#eqg-request-custom', function() {
            const email = $('#eqg-email').val();
            if (!email) {
                alert('Please enter your email first.');
                $('#eqg-email').focus();
                return;
            }
            $('#eqg-custom-product-modal').fadeIn();
        });
        
        // Close modal
        $(document).on('click', '.eqg-modal-close', function() {
            $(this).closest('.eqg-modal').fadeOut();
        });
        
        $(document).on('click', '.eqg-modal', function(e) {
            if (e.target === this) {
                $(this).fadeOut();
            }
        });
        
        // Submit custom product request
        $(document).on('submit', '#eqg-custom-product-form', function(e) {
            e.preventDefault();
            
            const productName = $('#eqg-custom-name').val();
            const description = $('#eqg-custom-description').val();
            const category = $('#eqg-custom-category').val();
            const email = $('#eqg-email').val();
            
            $.ajax({
                url: eqgAjax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'eqg_request_custom_product',
                    nonce: eqgAjax.nonce,
                    product_name: productName,
                    description: description,
                    category: category,
                    email: email
                },
                beforeSend: function() {
                    $('#eqg-custom-product-form button').prop('disabled', true).text('Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        alert('Thank you! Your custom product request has been submitted. Admin will review and add pricing.');
                        $('#eqg-custom-product-modal').fadeOut();
                        $('#eqg-custom-product-form')[0].reset();
                    } else {
                        alert(response.data.message || 'Error submitting request.');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                },
                complete: function() {
                    $('#eqg-custom-product-form button').prop('disabled', false).text('Submit Request');
                }
            });
        });
        
        // Main quote form submission
        $(document).on('submit', '#eqg-quote-form', function(e) {
            e.preventDefault();
            
            if (cart.length === 0) {
                alert('Please add at least one product to your quote.');
                return;
            }
            
            const $form = $(this);
            const $submitBtn = $form.find('.eqg-submit-btn');
            const $message = $form.find('.eqg-message');
            const $btnText = $submitBtn.find('.eqg-btn-text');
            const $btnLoading = $submitBtn.find('.eqg-btn-loading');
            
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            $message.hide();
            
            const formData = {
                action: 'eqg_generate_quote',
                nonce: eqgAjax.nonce,
                name: $('#eqg-name').val(),
                email: $('#eqg-email').val(),
                cart_items: JSON.stringify(cart)
            };
            
            $.ajax({
                url: eqgAjax.ajaxurl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        $message
                            .removeClass('error')
                            .addClass('success')
                            .html(
                                response.data.message + 
                                '<br><br><a href="' + response.data.download_url + '" class="eqg-download-link" target="_blank">📥 Download PDF Quote</a>'
                            )
                            .show();
                        
                        // Reset form and cart
                        $form[0].reset();
                        cart = [];
                        renderCart();
                        
                        // Scroll to message
                        $('html, body').animate({
                            scrollTop: $message.offset().top - 100
                        }, 500);
                    } else {
                        $message
                            .removeClass('success')
                            .addClass('error')
                            .text(response.data.message || 'An error occurred.')
                            .show();
                    }
                },
                error: function() {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text('An error occurred. Please try again.')
                        .show();
                },
                complete: function() {
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                }
            });
        });
        
        // Popup functionality
        $(document).on('click', '.eqg-open-popup-btn', function(e) {
            e.preventDefault();
            $('#eqg-quote-popup').fadeIn(300);
            $('body').css('overflow', 'hidden');
        });
        
        $(document).on('click', '.eqg-popup-close', function() {
            $('#eqg-quote-popup').fadeOut(300);
            $('body').css('overflow', 'auto');
        });
        
        $(document).on('click', '.eqg-popup-overlay', function(e) {
            if ($(e.target).hasClass('eqg-popup-overlay')) {
                $('#eqg-quote-popup').fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });
        
        $(document).on('keyup', function(e) {
            if (e.key === 'Escape' || e.keyCode === 27) {
                $('#eqg-quote-popup').fadeOut(300);
                $('body').css('overflow', 'auto');
            }
        });
    });
    
    /**
     * Add product to cart
     */
    function addProductToCart($productCard) {
        const productData = {
            id: $productCard.data('id'),
            name: $productCard.data('name'),
            price: parseFloat($productCard.data('price')),
            description: $productCard.data('description')
        };
        
        // Check if product already in cart
        const existingItem = cart.find(item => item.product_id === productData.id);
        
        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                item_id: ++cartItemCounter,
                product_id: productData.id,
                name: productData.name,
                price: productData.price,
                quantity: 1,
                description: productData.description
            });
        }
        
        renderCart();
        
        // Visual feedback
        $productCard.addClass('product-added');
        setTimeout(function() {
            $productCard.removeClass('product-added');
        }, 300);
    }
    
    /**
     * Update cart item quantity
     */
    function updateCartItem(itemId, newQuantity) {
        const item = cart.find(i => i.item_id === itemId);
        if (item) {
            item.quantity = newQuantity;
            renderCart();
        }
    }
    
    /**
     * Remove item from cart
     */
    function removeCartItem(itemId) {
        cart = cart.filter(item => item.item_id !== itemId);
        renderCart();
    }
    
    /**
     * Render cart UI
     */
    function renderCart() {
        const $cartItems = $('#eqg-cart-items');
        
        if (cart.length === 0) {
            $cartItems.html(`
                <div class="eqg-empty-cart">
                    <span class="dashicons dashicons-cart"></span>
                    <p>Drag or click to add products to your quote</p>
                </div>
            `);
            $('#eqg-subtotal, #eqg-total').text('₱0.00');
            $('.eqg-submit-btn').prop('disabled', true);
            return;
        }
        
        let html = '';
        let subtotal = 0;
        
        cart.forEach(function(item) {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;
            
            html += `
                <div class="eqg-cart-item" data-item-id="${item.item_id}">
                    <div class="eqg-cart-item-drag">
                        <span class="dashicons dashicons-menu"></span>
                    </div>
                    <div class="eqg-cart-item-info">
                        <div class="eqg-cart-item-name">${item.name}</div>
                        <div class="eqg-cart-item-price">₱${item.price.toFixed(2)} each</div>
                    </div>
                    <div class="eqg-cart-item-quantity">
                        <button type="button" class="eqg-qty-btn qty-minus">−</button>
                        <input type="number" class="eqg-qty-input" value="${item.quantity}" min="1" readonly>
                        <button type="button" class="eqg-qty-btn qty-plus">+</button>
                    </div>
                    <div class="eqg-cart-item-total">₱${itemTotal.toFixed(2)}</div>
                    <button type="button" class="eqg-cart-item-remove" title="Remove">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            `;
        });
        
        $cartItems.html(html);
        
        // Update totals
        $('#eqg-subtotal, #eqg-total').text('₱' + subtotal.toFixed(2));
        
        // Enable submit button
        $('.eqg-submit-btn').prop('disabled', false);
        
        // Re-initialize sortable
        $cartItems.sortable('refresh');
    }
    
})(jQuery);