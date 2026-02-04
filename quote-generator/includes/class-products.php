<?php
if (!defined('ABSPATH')) exit;

class EQG_Products {
    
    public function __construct() {
        // AJAX handlers for product operations
        add_action('wp_ajax_eqg_search_products', [$this, 'search_products']);
        add_action('wp_ajax_nopriv_eqg_search_products', [$this, 'search_products']);
        
        add_action('wp_ajax_eqg_request_product', [$this, 'request_product']);
        add_action('wp_ajax_nopriv_eqg_request_product', [$this, 'request_product']);
        
        add_action('wp_ajax_eqg_update_product_price', [$this, 'update_product_price']);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Admin notifications
        add_action('admin_notices', [$this, 'pending_products_notice']);
    }
    
    /**
     * Add admin menu for product management
     */
    public function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=eqg_quote',
            __('Products', 'eqg'),
            __('Products', 'eqg'),
            'manage_options',
            'eqg-products',
            [$this, 'render_products_page']
        );
    }
    
    /**
     * Show notification for pending products
     */
    public function pending_products_notice() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'pending'");
        
        if ($pending_count > 0) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
                    <strong><?php _e('Quote Generator:', 'eqg'); ?></strong>
                    <?php printf(__('You have %d pending product request(s) awaiting price.', 'eqg'), $pending_count); ?>
                    <a href="<?php echo admin_url('edit.php?post_type=eqg_quote&page=eqg-products'); ?>">
                        <?php _e('View Now', 'eqg'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Render products management page
     */
    public function render_products_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        // Handle price update
        if (isset($_POST['update_price']) && check_admin_referer('eqg_update_price')) {
            $product_id = intval($_POST['product_id']);
            $price = floatval($_POST['price']);
            
            $wpdb->update(
                $table_name,
                ['price' => $price, 'status' => 'approved'],
                ['id' => $product_id],
                ['%f', '%s'],
                ['%d']
            );
            
            // Send notification email to requester
            $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $product_id));
            if ($product && $product->requested_by) {
                $this->send_price_notification($product);
            }
            
            echo '<div class="notice notice-success"><p>' . __('Price updated successfully!', 'eqg') . '</p></div>';
        }
        
        // Get all products
        $products = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Product Requests', 'eqg'); ?></h1>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'eqg'); ?></th>
                        <th><?php _e('Product Name', 'eqg'); ?></th>
                        <th><?php _e('Description', 'eqg'); ?></th>
                        <th><?php _e('Price', 'eqg'); ?></th>
                        <th><?php _e('Status', 'eqg'); ?></th>
                        <th><?php _e('Requested By', 'eqg'); ?></th>
                        <th><?php _e('Date', 'eqg'); ?></th>
                        <th><?php _e('Actions', 'eqg'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8"><?php _e('No products found.', 'eqg'); ?></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo esc_html($product->id); ?></td>
                                <td><strong><?php echo esc_html($product->product_name); ?></strong></td>
                                <td><?php echo esc_html($product->description); ?></td>
                                <td>
                                    <?php if ($product->price): ?>
                                        ₱<?php echo number_format($product->price, 2); ?>
                                    <?php else: ?>
                                        <span style="color: #999;"><?php _e('Not set', 'eqg'); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product->status === 'pending'): ?>
                                        <span class="status-badge" style="background: #f0ad4e; color: #fff; padding: 3px 8px; border-radius: 3px;">
                                            <?php _e('Pending', 'eqg'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge" style="background: #5cb85c; color: #fff; padding: 3px 8px; border-radius: 3px;">
                                            <?php _e('Approved', 'eqg'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($product->requested_by ?: '-'); ?></td>
                                <td><?php echo esc_html(date('M d, Y', strtotime($product->created_at))); ?></td>
                                <td>
                                    <?php if ($product->status === 'pending'): ?>
                                        <button class="button button-primary" onclick="openPriceModal(<?php echo $product->id; ?>, '<?php echo esc_js($product->product_name); ?>')">
                                            <?php _e('Set Price', 'eqg'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button class="button" onclick="openPriceModal(<?php echo $product->id; ?>, '<?php echo esc_js($product->product_name); ?>')">
                                            <?php _e('Update Price', 'eqg'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Price Update Modal -->
        <div id="price-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; min-width: 400px;">
                <h2 id="modal-title"><?php _e('Set Product Price', 'eqg'); ?></h2>
                <form method="post">
                    <?php wp_nonce_field('eqg_update_price'); ?>
                    <input type="hidden" name="product_id" id="modal-product-id">
                    <p>
                        <label><strong><?php _e('Product:', 'eqg'); ?></strong></label><br>
                        <span id="modal-product-name"></span>
                    </p>
                    <p>
                        <label><strong><?php _e('Price (₱):', 'eqg'); ?></strong></label><br>
                        <input type="number" name="price" step="0.01" min="0" required style="width: 100%; padding: 8px;">
                    </p>
                    <p>
                        <button type="submit" name="update_price" class="button button-primary"><?php _e('Update Price', 'eqg'); ?></button>
                        <button type="button" class="button" onclick="closePriceModal()"><?php _e('Cancel', 'eqg'); ?></button>
                    </p>
                </form>
            </div>
        </div>
        
        <script>
        function openPriceModal(productId, productName) {
            document.getElementById('modal-product-id').value = productId;
            document.getElementById('modal-product-name').textContent = productName;
            document.getElementById('price-modal').style.display = 'block';
        }
        
        function closePriceModal() {
            document.getElementById('price-modal').style.display = 'none';
        }
        </script>
        <?php
    }
    
    /**
     * Search products with autocomplete
     */
    public function search_products() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        
        if (strlen($search) < 2) {
            wp_send_json_success(['products' => []]);
        }
        
        $products = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name 
            WHERE product_name LIKE %s 
            AND status = 'approved' 
            ORDER BY product_name ASC 
            LIMIT 10",
            '%' . $wpdb->esc_like($search) . '%'
        ));
        
        wp_send_json_success(['products' => $products]);
    }
    
    /**
     * Request new custom product
     */
    public function request_product() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        check_ajax_referer('eqg_ajax_nonce', 'nonce');
        
        $product_name = sanitize_text_field($_POST['product_name']);
        $description = sanitize_textarea_field($_POST['description']);
        $requested_by = sanitize_email($_POST['email']);
        
        // Check if product already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_name = %s",
            $product_name
        ));
        
        if ($existing) {
            if ($existing->status === 'approved') {
                wp_send_json_success([
                    'message' => __('This product already exists with a price!', 'eqg'),
                    'product' => $existing
                ]);
            } else {
                wp_send_json_error([
                    'message' => __('This product is already requested and awaiting admin approval.', 'eqg')
                ]);
            }
            return;
        }
        
        // Insert new product request
        $wpdb->insert(
            $table_name,
            [
                'product_name' => $product_name,
                'description' => $description,
                'requested_by' => $requested_by,
                'status' => 'pending'
            ],
            ['%s', '%s', '%s', '%s']
        );
        
        // Send notification to admin
        $this->send_admin_notification($product_name, $description, $requested_by);
        
        wp_send_json_success([
            'message' => __('Product request submitted! Admin will set the price soon.', 'eqg')
        ]);
    }
    
    /**
     * Send notification to admin about new product request
     */
    private function send_admin_notification($product_name, $description, $requested_by) {
        $to = get_option('admin_email');
        $subject = sprintf(__('[%s] New Product Request', 'eqg'), get_bloginfo('name'));
        
        $message = sprintf(
            __("A new custom product has been requested:\n\nProduct: %s\nDescription: %s\nRequested by: %s\n\nPlease set a price: %s", 'eqg'),
            $product_name,
            $description,
            $requested_by,
            admin_url('edit.php?post_type=eqg_quote&page=eqg-products')
        );
        
        wp_mail($to, $subject, $message);
    }
    
    /**
     * Send notification to customer when price is set
     */
    private function send_price_notification($product) {
        $to = $product->requested_by;
        $subject = sprintf(__('[%s] Your Product Request - Price Available', 'eqg'), get_bloginfo('name'));
        
        $message = sprintf(
            __("Good news! The price for your requested product is now available:\n\nProduct: %s\nPrice: ₱%s\n\nYou can now request a quote for this product.", 'eqg'),
            $product->product_name,
            number_format($product->price, 2)
        );
        
        wp_mail($to, $subject, $message);
    }
}

new EQG_Products();