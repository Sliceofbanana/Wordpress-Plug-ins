<?php
if (!defined('ABSPATH')) exit;

class EQG_Admin_Columns {
    
    public function __construct() {
        add_filter('manage_eqg_quote_posts_columns', [$this, 'add_custom_columns']);
        add_action('manage_eqg_quote_posts_custom_column', [$this, 'render_custom_columns'], 10, 2);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
    }
    
    public function add_custom_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['customer_name'] = __('Customer Name', 'eqg');
        $new_columns['customer_email'] = __('Email', 'eqg');
        $new_columns['service'] = __('Service', 'eqg');
        $new_columns['amount'] = __('Amount', 'eqg');
        $new_columns['email_sent'] = __('Email Sent', 'eqg');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    public function render_custom_columns($column, $post_id) {
        $data = get_post_meta($post_id, '_eqg_data', true);
        
        switch ($column) {
            case 'customer_name':
                echo esc_html($data['name'] ?? '-');
                break;
                
            case 'customer_email':
                echo esc_html($data['email'] ?? '-');
                break;
                
            case 'service':
                echo esc_html($data['service'] ?? '-');
                break;
                
            case 'amount':
                $amount = $data['amount'] ?? 0;
                echo '₱' . number_format($amount, 2);
                break;
                
            case 'email_sent':
                $sent = get_post_meta($post_id, '_eqg_email_sent', true);
                if ($sent === 'yes') {
                    echo '<span style="color: green;">✓ ' . __('Sent', 'eqg') . '</span>';
                } else {
                    echo '<span style="color: red;">✗ ' . __('Not Sent', 'eqg') . '</span>';
                }
                break;
        }
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'eqg_quote_details',
            __('Quote Details', 'eqg'),
            [$this, 'render_quote_details_meta_box'],
            'eqg_quote',
            'normal',
            'high'
        );
        
        add_meta_box(
            'eqg_quote_actions',
            __('Actions', 'eqg'),
            [$this, 'render_quote_actions_meta_box'],
            'eqg_quote',
            'side',
            'high'
        );
    }
    
    public function render_quote_details_meta_box($post) {
        $data = get_post_meta($post->ID, '_eqg_data', true);
        $date = get_post_meta($post->ID, '_eqg_date', true);
        $email_sent = get_post_meta($post->ID, '_eqg_email_sent', true);
        $email_date = get_post_meta($post->ID, '_eqg_email_sent_date', true);
        
        if (!$data) {
            echo '<p>' . __('No quote data available.', 'eqg') . '</p>';
            return;
        }
        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('Customer Name', 'eqg'); ?></th>
                <td><?php echo esc_html($data['name']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Email', 'eqg'); ?></th>
                <td><?php echo esc_html($data['email']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Service', 'eqg'); ?></th>
                <td><?php echo esc_html($data['service']); ?></td>
            </tr>
            <tr>
                <th><?php _e('Amount', 'eqg'); ?></th>
                <td>₱<?php echo number_format($data['amount'], 2); ?></td>
            </tr>
            <tr>
                <th><?php _e('Date Created', 'eqg'); ?></th>
                <td><?php echo $date ? esc_html($date) : '-'; ?></td>
            </tr>
            <tr>
                <th><?php _e('Email Status', 'eqg'); ?></th>
                <td>
                    <?php if ($email_sent === 'yes'): ?>
                        <span style="color: green;">✓ <?php _e('Sent', 'eqg'); ?></span>
                        <?php if ($email_date): ?>
                            <br><small><?php echo esc_html($email_date); ?></small>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color: red;">✗ <?php _e('Not Sent', 'eqg'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }
    
    public function render_quote_actions_meta_box($post) {
        $download_url = add_query_arg([
            'action' => 'eqg_download_pdf',
            'quote_id' => $post->ID,
            'nonce' => wp_create_nonce('eqg_download_' . $post->ID)
        ], admin_url('admin-ajax.php'));
        ?>
        <div class="submitbox">
            <div style="padding: 10px;">
                <a href="<?php echo esc_url($download_url); ?>" class="button button-primary button-large" target="_blank">
                    <?php _e('Download PDF', 'eqg'); ?>
                </a>
            </div>
        </div>
        <?php
    }
}

new EQG_Admin_Columns();