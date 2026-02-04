<?php
if (!defined('ABSPATH')) exit;

class EQG_Ajax_Handler {
    
    public function __construct() {
        add_action('wp_ajax_eqg_generate_quote', [$this, 'handle_quote_generation']);
        add_action('wp_ajax_nopriv_eqg_generate_quote', [$this, 'handle_quote_generation']);
    }
    
    public function handle_quote_generation() {
        try {
            // Verify nonce
            if (!check_ajax_referer('eqg_ajax_nonce', 'nonce', false)) {
                throw new Exception(__('Security check failed.', 'eqg'));
            }
            
            // Validate and sanitize input
            $data = $this->validate_input($_POST);
            
            // Create quote post
            $quote_id = wp_insert_post([
                'post_type'   => 'eqg_quote',
                'post_title'  => sprintf(__('Quote - %s', 'eqg'), $data['name']),
                'post_status' => 'publish'
            ]);
            
            if (is_wp_error($quote_id)) {
                throw new Exception(__('Failed to create quote.', 'eqg'));
            }
            
            // Store quote data
            update_post_meta($quote_id, '_eqg_data', $data);
            update_post_meta($quote_id, '_eqg_date', current_time('mysql'));
            
            // Generate PDF
            $pdf_path = EQG_PDF_Generator::generate($quote_id, $data);
            
            // Send email
            EQG_Email_Handler::send_quote_email($quote_id, $data, $pdf_path);
            
            wp_send_json_success([
                'message' => __('Quote generated successfully!', 'eqg'),
                'quote_id' => $quote_id,
                'download_url' => add_query_arg([
                    'action' => 'eqg_download_pdf',
                    'quote_id' => $quote_id,
                    'nonce' => wp_create_nonce('eqg_download_' . $quote_id)
                ], admin_url('admin-ajax.php'))
            ]);
            
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }
    
    private function validate_input($post_data) {
        $errors = [];
        
        // Validate name
        $name = isset($post_data['name']) ? sanitize_text_field($post_data['name']) : '';
        if (empty($name)) {
            $errors[] = __('Name is required.', 'eqg');
        }
        
        // Validate email
        $email = isset($post_data['email']) ? sanitize_email($post_data['email']) : '';
        if (empty($email) || !is_email($email)) {
            $errors[] = __('Valid email is required.', 'eqg');
        }
        
        // Validate service
        $service = isset($post_data['service']) ? sanitize_text_field($post_data['service']) : '';
        if (empty($service)) {
            $errors[] = __('Service is required.', 'eqg');
        }
        
        // Validate amount
        $amount = isset($post_data['amount']) ? floatval($post_data['amount']) : 0;
        if ($amount <= 0) {
            $errors[] = __('Amount must be greater than zero.', 'eqg');
        }
        
        if (!empty($errors)) {
            throw new Exception(implode(' ', $errors));
        }
        
        return [
            'name'    => $name,
            'email'   => $email,
            'service' => $service,
            'amount'  => $amount,
        ];
    }
}

new EQG_Ajax_Handler();

// Handle PDF download
add_action('wp_ajax_eqg_download_pdf', function() {
    if (!isset($_GET['quote_id']) || !isset($_GET['nonce'])) {
        wp_die(__('Invalid request.', 'eqg'));
    }
    
    $quote_id = intval($_GET['quote_id']);
    
    if (!wp_verify_nonce($_GET['nonce'], 'eqg_download_' . $quote_id)) {
        wp_die(__('Security check failed.', 'eqg'));
    }
    
    $data = get_post_meta($quote_id, '_eqg_data', true);
    
    if (!$data) {
        wp_die(__('Quote not found.', 'eqg'));
    }
    
    EQG_PDF_Generator::stream($quote_id, $data);
});

add_action('wp_ajax_nopriv_eqg_download_pdf', function() {
    do_action('wp_ajax_eqg_download_pdf');
});