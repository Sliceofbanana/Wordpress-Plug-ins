<?php
if (!defined('ABSPATH')) exit;

add_shortcode('quote_generator', function () {
    global $wpdb;
    $products_table = $wpdb->prefix . 'eqg_products';
    
    // Get all active products grouped by category
    $products = $wpdb->get_results("
        SELECT * FROM $products_table 
        WHERE status = 'active' 
        ORDER BY category, product_name ASC
    ");
    
    // Group products by category
    $grouped_products = [];
    foreach ($products as $product) {
        $category = $product->category ?: __('Uncategorized', 'eqg');
        if (!isset($grouped_products[$category])) {
            $grouped_products[$category] = [];
        }
        $grouped_products[$category][] = $product;
    }
    
    ob_start(); 
    ?>
    <div class="eqg-quote-builder-wrapper">
        <form id="eqg-quote-form" class="eqg-quote-form">
            <?php wp_nonce_field('eqg_ajax_nonce', 'eqg_nonce'); ?>
            
            <!-- Customer Information -->
            <div class="eqg-section">
                <h3><?php _e('Customer Information', 'eqg'); ?></h3>
                
                <div class="eqg-form-row">
                    <label for="eqg-name"><?php _e('Your Name', 'eqg'); ?> <span class="required">*</span></label>
                    <input type="text" id="eqg-name" name="name" placeholder="<?php esc_attr_e('Enter your name', 'eqg'); ?>" required>
                </div>
                
                <div class="eqg-form-row">
                    <label for="eqg-email"><?php _e('Email', 'eqg'); ?> <span class="required">*</span></label>
                    <input type="email" id="eqg-email" name="email" placeholder="<?php esc_attr_e('your@email.com', 'eqg'); ?>" required>
                </div>
            </div>
            
            <!-- Product Builder -->
            <div class="eqg-section">
                <h3><?php _e('Build Your Quote', 'eqg'); ?></h3>
                
                <div class="eqg-builder-container">
                    <!-- Available Products Sidebar -->
                    <div class="eqg-products-sidebar">
                        <div class="eqg-search-box">
                            <input type="text" id="eqg-product-search" placeholder="<?php esc_attr_e('Search products...', 'eqg'); ?>">
                        </div>
                        
                        <div class="eqg-products-list">
                            <?php foreach ($grouped_products as $category => $category_products): ?>
                                <div class="eqg-category-group">
                                    <h4><?php echo esc_html($category); ?></h4>
                                    <div class="eqg-category-products">
                                        <?php foreach ($category_products as $product): ?>
                                            <div class="eqg-product-card" 
                                                 data-id="<?php echo esc_attr($product->id); ?>"
                                                 data-name="<?php echo esc_attr($product->product_name); ?>"
                                                 data-price="<?php echo esc_attr($product->price); ?>"
                                                 data-description="<?php echo esc_attr($product->description); ?>">
                                                <div class="eqg-product-info">
                                                    <strong><?php echo esc_html($product->product_name); ?></strong>
                                                    <span class="eqg-product-price">₱<?php echo number_format($product->price, 2); ?></span>
                                                </div>
                                                <button type="button" class="eqg-add-product-btn" title="<?php _e('Add to quote', 'eqg'); ?>">
                                                    <span class="dashicons dashicons-plus-alt"></span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($grouped_products)): ?>
                                <p class="eqg-no-products"><?php _e('No products available. Please contact admin.', 'eqg'); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Request Custom Product Button -->
                        <button type="button" id="eqg-request-custom" class="eqg-request-custom-btn">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php _e('Request Custom Product', 'eqg'); ?>
                        </button>
                    </div>
                    
                    <!-- Selected Products Area -->
                    <div class="eqg-selected-products">
                        <h4><?php _e('Selected Products/Services', 'eqg'); ?></h4>
                        
                        <div id="eqg-cart-items" class="eqg-cart-items">
                            <div class="eqg-empty-cart">
                                <span class="dashicons dashicons-cart"></span>
                                <p><?php _e('Drag or click to add products to your quote', 'eqg'); ?></p>
                            </div>
                        </div>
                        
                        <!-- Quote Summary -->
                        <div class="eqg-quote-summary">
                            <div class="eqg-summary-row">
                                <span><?php _e('Subtotal:', 'eqg'); ?></span>
                                <strong id="eqg-subtotal">₱0.00</strong>
                            </div>
                            <div class="eqg-summary-row eqg-total-row">
                                <span><?php _e('Total:', 'eqg'); ?></span>
                                <strong id="eqg-total">₱0.00</strong>
                            </div>
                        </div>
                        
                        <button type="submit" class="eqg-submit-btn" disabled>
                            <span class="eqg-btn-text"><?php _e('Generate Quote', 'eqg'); ?></span>
                            <span class="eqg-btn-loading" style="display:none;"><?php _e('Processing...', 'eqg'); ?></span>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="eqg-message" style="display:none;"></div>
        </form>
        
        <!-- Custom Product Request Modal -->
        <div id="eqg-custom-product-modal" class="eqg-modal" style="display:none;">
            <div class="eqg-modal-content">
                <span class="eqg-modal-close">&times;</span>
                <h3><?php _e('Request Custom Product/Service', 'eqg'); ?></h3>
                <p><?php _e('Describe the product or service you need. Admin will review and add it with pricing.', 'eqg'); ?></p>
                
                <form id="eqg-custom-product-form">
                    <div class="eqg-form-row">
                        <label><?php _e('Product/Service Name', 'eqg'); ?> <span class="required">*</span></label>
                        <input type="text" id="eqg-custom-name" required>
                    </div>
                    <div class="eqg-form-row">
                        <label><?php _e('Description', 'eqg'); ?></label>
                        <textarea id="eqg-custom-description" rows="4" placeholder="<?php esc_attr_e('Provide details about what you need...', 'eqg'); ?>"></textarea>
                    </div>
                    <div class="eqg-form-row">
                        <label><?php _e('Category', 'eqg'); ?></label>
                        <input type="text" id="eqg-custom-category" placeholder="<?php esc_attr_e('e.g., Printing, Design', 'eqg'); ?>">
                    </div>
                    <button type="submit" class="eqg-submit-btn"><?php _e('Submit Request', 'eqg'); ?></button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

// Shortcode for popup trigger button
add_shortcode('quote_button', function($atts) {
    $atts = shortcode_atts([
        'text' => __('Get a Quote', 'eqg'),
        'class' => 'eqg-popup-trigger'
    ], $atts);
    
    ob_start();
    ?>
    <button class="<?php echo esc_attr($atts['class']); ?> eqg-open-popup-btn" type="button">
        <?php echo esc_html($atts['text']); ?>
    </button>
    
    <div id="eqg-quote-popup" class="eqg-popup-overlay" style="display:none;">
        <div class="eqg-popup-container">
            <span class="eqg-popup-close">&times;</span>
            <?php echo do_shortcode('[quote_generator]'); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});