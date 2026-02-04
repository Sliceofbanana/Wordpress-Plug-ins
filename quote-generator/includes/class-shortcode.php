<?php
// filepath: c:\XAMPP\htdocs\mqprinting\wp-content\plugins\quote-generator\includes\class-shortcode.php
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
    
    // Check if products exist
    if (empty($products)) {
        return '<div class="eqg-no-products-message"><p>' . __('No products available at the moment. Please check back later or contact us directly.', 'eqg') . '</p></div>';
    }
    
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
        <div class="eqg-quote-header">
            <h2><?php _e('Build Your Quote', 'eqg'); ?></h2>
            <p><?php _e('Select products and services, adjust quantities, and generate your customized quote.', 'eqg'); ?></p>
        </div>
        
        <form id="eqg-quote-form" class="eqg-quote-form">
            <?php wp_nonce_field('eqg_ajax_nonce', 'eqg_nonce'); ?>
            
            <!-- Customer Information Section -->
            <div class="eqg-section eqg-customer-section">
                <h3><?php _e('Customer Information', 'eqg'); ?></h3>
                
                <div class="eqg-form-grid">
                    <div class="eqg-form-row">
                        <label for="eqg-name"><?php _e('Full Name', 'eqg'); ?> <span class="required">*</span></label>
                        <input type="text" id="eqg-name" name="name" placeholder="<?php esc_attr_e('e.g., John Doe', 'eqg'); ?>" required>
                    </div>
                    
                    <div class="eqg-form-row">
                        <label for="eqg-email"><?php _e('Email Address', 'eqg'); ?> <span class="required">*</span></label>
                        <input type="email" id="eqg-email" name="email" placeholder="<?php esc_attr_e('your@email.com', 'eqg'); ?>" required>
                    </div>
                </div>
            </div>
            
            <!-- Product Builder Section -->
            <div class="eqg-section eqg-builder-section">
                <h3><?php _e('Select Products & Services', 'eqg'); ?></h3>
                
                <div class="eqg-builder-container">
                    <!-- Available Products Sidebar -->
                    <div class="eqg-products-sidebar">
                        <div class="eqg-sidebar-header">
                            <h4><?php _e('Available Products', 'eqg'); ?></h4>
                            <div class="eqg-search-box">
                                <span class="dashicons dashicons-search"></span>
                                <input type="text" id="eqg-product-search" placeholder="<?php esc_attr_e('Search...', 'eqg'); ?>">
                            </div>
                        </div>
                        
                        <div class="eqg-products-list">
                            <?php foreach ($grouped_products as $category => $category_products): ?>
                                <div class="eqg-category-group">
                                    <h5 class="eqg-category-title"><?php echo esc_html($category); ?></h5>
                                    <div class="eqg-category-products">
                                        <?php foreach ($category_products as $product): ?>
                                            <div class="eqg-product-card" 
                                                 data-id="<?php echo esc_attr($product->id); ?>"
                                                 data-name="<?php echo esc_attr($product->product_name); ?>"
                                                 data-price="<?php echo esc_attr($product->price); ?>"
                                                 data-description="<?php echo esc_attr($product->description); ?>">
                                                <div class="eqg-product-info">
                                                    <div class="eqg-product-name"><?php echo esc_html($product->product_name); ?></div>
                                                    <?php if ($product->description): ?>
                                                        <div class="eqg-product-desc"><?php echo esc_html(wp_trim_words($product->description, 8)); ?></div>
                                                    <?php endif; ?>
                                                    <div class="eqg-product-price">₱<?php echo number_format($product->price, 2); ?></div>
                                                </div>
                                                <button type="button" class="eqg-add-product-btn" title="<?php _e('Add to quote', 'eqg'); ?>">
                                                    <span class="dashicons dashicons-plus-alt"></span>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Request Custom Product Button -->
                        <button type="button" id="eqg-request-custom" class="eqg-request-custom-btn">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <?php _e('Need Something Custom?', 'eqg'); ?>
                        </button>
                    </div>
                    
                    <!-- Selected Products Area (Quote Table) -->
                    <div class="eqg-selected-products">
                        <div class="eqg-cart-header">
                            <h4><?php _e('Your Quote', 'eqg'); ?></h4>
                        </div>
                        
                        <div class="eqg-cart-table-container">
                            <table class="eqg-cart-table">
                                <thead>
                                    <tr>
                                        <th class="col-drag"></th>
                                        <th class="col-item"><?php _e('Item Details', 'eqg'); ?></th>
                                        <th class="col-qty"><?php _e('QTY', 'eqg'); ?></th>
                                        <th class="col-price"><?php _e('Unit Price', 'eqg'); ?></th>
                                        <th class="col-total"><?php _e('Total', 'eqg'); ?></th>
                                        <th class="col-remove"></th>
                                    </tr>
                                </thead>
                                <tbody id="eqg-cart-items">
                                    <tr class="eqg-empty-cart-row">
                                        <td colspan="6" class="eqg-empty-cart">
                                            <span class="dashicons dashicons-cart"></span>
                                            <p><?php _e('Click the + button or drag products here to build your quote', 'eqg'); ?></p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Quote Summary -->
                        <div class="eqg-quote-summary">
                            <div class="eqg-summary-rows">
                                <div class="eqg-summary-row">
                                    <span><?php _e('Subtotal:', 'eqg'); ?></span>
                                    <strong id="eqg-subtotal">₱0.00</strong>
                                </div>
                                <div class="eqg-summary-row eqg-total-row">
                                    <span><?php _e('Grand Total:', 'eqg'); ?></span>
                                    <strong id="eqg-total">₱0.00</strong>
                                </div>
                            </div>
                            
                            <button type="submit" class="eqg-submit-btn" disabled>
                                <span class="dashicons dashicons-pdf"></span>
                                <span class="eqg-btn-text"><?php _e('Generate PDF / Print', 'eqg'); ?></span>
                                <span class="eqg-btn-loading" style="display:none;"><?php _e('Generating...', 'eqg'); ?></span>
                            </button>
                            
                            <p class="eqg-quote-validity"><?php _e('Quotation is valid for 30 days from today.', 'eqg'); ?></p>
                        </div>
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
                <p><?php _e('Can\'t find what you need? Describe your requirements and we\'ll add it with pricing.', 'eqg'); ?></p>
                
                <form id="eqg-custom-product-form">
                    <div class="eqg-form-row">
                        <label><?php _e('Product/Service Name', 'eqg'); ?> <span class="required">*</span></label>
                        <input type="text" id="eqg-custom-name" placeholder="<?php esc_attr_e('e.g., Custom Banner 10ft x 20ft', 'eqg'); ?>" required>
                    </div>
                    <div class="eqg-form-row">
                        <label><?php _e('Description & Requirements', 'eqg'); ?></label>
                        <textarea id="eqg-custom-description" rows="4" placeholder="<?php esc_attr_e('Please provide details about specifications, materials, quantity, etc.', 'eqg'); ?>"></textarea>
                    </div>
                    <div class="eqg-form-row">
                        <label><?php _e('Category', 'eqg'); ?></label>
                        <input type="text" id="eqg-custom-category" placeholder="<?php esc_attr_e('e.g., Printing, Design, Marketing', 'eqg'); ?>">
                    </div>
                    <div class="eqg-modal-actions">
                        <button type="button" class="eqg-btn-secondary" onclick="jQuery('#eqg-custom-product-modal').fadeOut();">
                            <?php _e('Cancel', 'eqg'); ?>
                        </button>
                        <button type="submit" class="eqg-submit-btn">
                            <?php _e('Submit Request', 'eqg'); ?>
                        </button>
                    </div>
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