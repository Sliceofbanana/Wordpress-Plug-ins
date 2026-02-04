<?php
if (!defined('ABSPATH')) exit;

class EQG_Product_Admin {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menus']);
        add_action('admin_post_eqg_save_product', [$this, 'save_product']);
        add_action('admin_post_eqg_delete_product', [$this, 'delete_product']);
        add_action('admin_notices', [$this, 'show_admin_notices']);
    }
    
    public function show_admin_notices() {
        if (isset($_GET['message'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(urldecode($_GET['message'])) . '</p></div>';
        }
    }
    
    public function add_admin_menus() {
        add_submenu_page(
            'edit.php?post_type=eqg_quote',
            __('Products & Services', 'eqg'),
            __('Products & Services', 'eqg'),
            'manage_options',
            'eqg-products',
            [$this, 'render_products_page']
        );
        
        add_submenu_page(
            'edit.php?post_type=eqg_quote',
            __('Add Product/Service', 'eqg'),
            __('Add New Product', 'eqg'),
            'manage_options',
            'eqg-add-product',
            [$this, 'render_add_product_page']
        );
    }
    
    public function render_products_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        // Get all products
        $products = $wpdb->get_results("SELECT * FROM $table_name ORDER BY category, product_name ASC");
        
        // Group by category for better display
        $grouped = [];
        foreach ($products as $product) {
            $cat = $product->category ?: __('Uncategorized', 'eqg');
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = [];
            }
            $grouped[$cat][] = $product;
        }
        
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Products & Services', 'eqg'); ?></h1>
            <a href="<?php echo admin_url('edit.php?post_type=eqg_quote&page=eqg-add-product'); ?>" class="page-title-action">
                <?php _e('Add New', 'eqg'); ?>
            </a>
            <hr class="wp-header-end">
            
            <?php if (empty($products)): ?>
                <div class="notice notice-info" style="margin-top: 20px;">
                    <p>
                        <?php _e('No products found. Add your first product to get started!', 'eqg'); ?>
                        <a href="<?php echo admin_url('edit.php?post_type=eqg_quote&page=eqg-add-product'); ?>" class="button button-primary">
                            <?php _e('Add Product', 'eqg'); ?>
                        </a>
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped as $category => $category_products): ?>
                    <h2><?php echo esc_html($category); ?></h2>
                    <table class="wp-list-table widefat fixed striped" style="margin-bottom: 30px;">
                        <thead>
                            <tr>
                                <th width="5%"><?php _e('ID', 'eqg'); ?></th>
                                <th width="25%"><?php _e('Product/Service Name', 'eqg'); ?></th>
                                <th width="35%"><?php _e('Description', 'eqg'); ?></th>
                                <th width="12%"><?php _e('Price', 'eqg'); ?></th>
                                <th width="10%"><?php _e('Status', 'eqg'); ?></th>
                                <th width="13%"><?php _e('Actions', 'eqg'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($category_products as $product): ?>
                                <tr>
                                    <td><?php echo esc_html($product->id); ?></td>
                                    <td><strong><?php echo esc_html($product->product_name); ?></strong></td>
                                    <td><?php echo esc_html(wp_trim_words($product->description, 15)); ?></td>
                                    <td><strong>₱<?php echo number_format($product->price, 2); ?></strong></td>
                                    <td>
                                        <?php if ($product->status === 'active'): ?>
                                            <span style="color: #46b450;">● <?php _e('Active', 'eqg'); ?></span>
                                        <?php else: ?>
                                            <span style="color: #dc3232;">● <?php _e('Inactive', 'eqg'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo admin_url('edit.php?post_type=eqg_quote&page=eqg-add-product&edit=' . $product->id); ?>" class="button button-small">
                                            <?php _e('Edit', 'eqg'); ?>
                                        </a>
                                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="action" value="eqg_delete_product">
                                            <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                                            <?php wp_nonce_field('eqg_delete_product'); ?>
                                            <button type="submit" class="button button-small button-link-delete" onclick="return confirm('<?php _e('Are you sure you want to delete this product?', 'eqg'); ?>');" style="color: #b32d2e;">
                                                <?php _e('Delete', 'eqg'); ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
    }
    
    public function render_add_product_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        $product = null;
        $is_edit = false;
        
        if (isset($_GET['edit'])) {
            $product_id = intval($_GET['edit']);
            $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $product_id));
            $is_edit = true;
        }
        
        // Get existing categories for dropdown
        $categories = $wpdb->get_col("SELECT DISTINCT category FROM $table_name WHERE category IS NOT NULL AND category != '' ORDER BY category");
        
        ?>
        <div class="wrap">
            <h1><?php echo $is_edit ? __('Edit Product/Service', 'eqg') : __('Add New Product/Service', 'eqg'); ?></h1>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="max-width: 800px;">
                <input type="hidden" name="action" value="eqg_save_product">
                <?php wp_nonce_field('eqg_save_product'); ?>
                
                <?php if ($is_edit): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product->id; ?>">
                <?php endif; ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="product_name"><?php _e('Product/Service Name', 'eqg'); ?> <span class="description">(required)</span></label>
                        </th>
                        <td>
                            <input type="text" name="product_name" id="product_name" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product->product_name) : ''; ?>" required>
                            <p class="description"><?php _e('The name that will appear to customers', 'eqg'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="description"><?php _e('Description', 'eqg'); ?></label>
                        </th>
                        <td>
                            <textarea name="description" id="description" rows="5" class="large-text"><?php echo $product ? esc_textarea($product->description) : ''; ?></textarea>
                            <p class="description"><?php _e('Optional detailed description of the product/service', 'eqg'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="category"><?php _e('Category', 'eqg'); ?></label>
                        </th>
                        <td>
                            <input type="text" name="category" id="category" class="regular-text" list="eqg-categories"
                                   value="<?php echo $product ? esc_attr($product->category) : ''; ?>"
                                   placeholder="<?php _e('e.g., Printing, Design, Marketing', 'eqg'); ?>">
                            <datalist id="eqg-categories">
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo esc_attr($cat); ?>">
                                <?php endforeach; ?>
                            </datalist>
                            <p class="description"><?php _e('Used to group products in the quote form. Start typing to see existing categories.', 'eqg'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="price"><?php _e('Price (₱)', 'eqg'); ?> <span class="description">(required)</span></label>
                        </th>
                        <td>
                            <input type="number" name="price" id="price" step="0.01" min="0" class="regular-text" 
                                   value="<?php echo $product ? esc_attr($product->price) : ''; ?>" required>
                            <p class="description"><?php _e('Base price for this product/service', 'eqg'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="status"><?php _e('Status', 'eqg'); ?></label>
                        </th>
                        <td>
                            <select name="status" id="status">
                                <option value="active" <?php echo ($product && $product->status === 'active') ? 'selected' : ''; ?>>
                                    <?php _e('Active - Visible to customers', 'eqg'); ?>
                                </option>
                                <option value="inactive" <?php echo ($product && $product->status === 'inactive') ? 'selected' : ''; ?>>
                                    <?php _e('Inactive - Hidden from customers', 'eqg'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" class="button button-primary button-large">
                        <?php echo $is_edit ? __('Update Product', 'eqg') : __('Add Product', 'eqg'); ?>
                    </button>
                    <a href="<?php echo admin_url('edit.php?post_type=eqg_quote&page=eqg-products'); ?>" class="button button-large">
                        <?php _e('Cancel', 'eqg'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }
    
    public function save_product() {
        check_admin_referer('eqg_save_product');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'eqg'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        $data = [
            'product_name' => sanitize_text_field($_POST['product_name']),
            'description' => sanitize_textarea_field($_POST['description']),
            'category' => sanitize_text_field($_POST['category']),
            'price' => floatval($_POST['price']),
            'status' => sanitize_text_field($_POST['status']),
        ];
        
        if (isset($_POST['product_id']) && !empty($_POST['product_id'])) {
            // Update existing product
            $wpdb->update(
                $table_name,
                $data,
                ['id' => intval($_POST['product_id'])],
                ['%s', '%s', '%s', '%f', '%s'],
                ['%d']
            );
            $message = __('Product updated successfully!', 'eqg');
        } else {
            // Insert new product
            $data['created_by'] = get_current_user_id();
            $wpdb->insert($table_name, $data);
            $message = __('Product added successfully!', 'eqg');
        }
        
        wp_redirect(add_query_arg([
            'page' => 'eqg-products',
            'message' => urlencode($message)
        ], admin_url('edit.php?post_type=eqg_quote')));
        exit;
    }
    
    public function delete_product() {
        check_admin_referer('eqg_delete_product');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'eqg'));
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'eqg_products';
        
        $product_id = intval($_POST['product_id']);
        $wpdb->delete($table_name, ['id' => $product_id], ['%d']);
        
        wp_redirect(add_query_arg([
            'page' => 'eqg-products',
            'message' => urlencode(__('Product deleted successfully!', 'eqg'))
        ], admin_url('edit.php?post_type=eqg_quote')));
        exit;
    }
}

new EQG_Product_Admin();