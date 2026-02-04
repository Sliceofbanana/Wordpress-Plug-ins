<?php
if (!defined('ABSPATH')) exit;

class EQG_Email_Handler {
    
    public static function send_quote_email($quote_id, $data, $pdf_path) {
        $to = $data['email'];
        $subject = sprintf(__('Your Quotation #%d', 'eqg'), $quote_id);
        
        $message = self::get_email_template($quote_id, $data);
        
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        ];
        
        $attachments = [];
        if (file_exists($pdf_path)) {
            $attachments[] = $pdf_path;
        }
        
        $sent = wp_mail($to, $subject, $message, $headers, $attachments);
        
        // Log email status
        update_post_meta($quote_id, '_eqg_email_sent', $sent ? 'yes' : 'no');
        update_post_meta($quote_id, '_eqg_email_sent_date', current_time('mysql'));
        
        return $sent;
    }
    
    private static function get_email_template($quote_id, $data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #f4f4f4; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #fff; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                th { background: #f4f4f4; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
                </div>
                <div class="content">
                    <h2><?php _e('Thank you for your quote request!', 'eqg'); ?></h2>
                    <p><?php printf(__('Dear %s,', 'eqg'), esc_html($data['name'])); ?></p>
                    <p><?php _e('Please find your quotation details below:', 'eqg'); ?></p>
                    
                    <table>
                        <tr>
                            <th><?php _e('Quote ID', 'eqg'); ?></th>
                            <td>#<?php echo esc_html($quote_id); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Service', 'eqg'); ?></th>
                            <td><?php echo esc_html($data['service']); ?></td>
                        </tr>
                        <tr>
                            <th><?php _e('Amount', 'eqg'); ?></th>
                            <td>₱<?php echo number_format($data['amount'], 2); ?></td>
                        </tr>
                    </table>
                    
                    <p><?php _e('Your PDF quotation is attached to this email.', 'eqg'); ?></p>
                    <p><?php _e('If you have any questions, please don\'t hesitate to contact us.', 'eqg'); ?></p>
                </div>
                <div class="footer">
                    <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}