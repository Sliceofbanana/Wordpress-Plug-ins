<?php
use Dompdf\Dompdf;
use Dompdf\Options;

if (!defined('ABSPATH')) exit;

class EQG_PDF_Generator {

    public static function generate($quote_id, $data) {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf($options);
            
            $html = self::get_pdf_template($quote_id, $data);
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Save to uploads directory
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/eqg-quotes';
            
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }
            
            $filename = "quotation-{$quote_id}.pdf";
            $filepath = $pdf_dir . '/' . $filename;
            
            file_put_contents($filepath, $dompdf->output());
            
            // Store file path in post meta
            update_post_meta($quote_id, '_eqg_pdf_path', $filepath);
            
            return $filepath;
            
        } catch (Exception $e) {
            error_log('EQG PDF Generation Error: ' . $e->getMessage());
            throw new Exception(__('Failed to generate PDF.', 'eqg'));
        }
    }
    
    public static function stream($quote_id, $data) {
        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);
            
            $dompdf = new Dompdf($options);
            
            $html = self::get_pdf_template($quote_id, $data);
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            $dompdf->stream("quotation-{$quote_id}.pdf", ["Attachment" => true]);
            exit;
            
        } catch (Exception $e) {
            error_log('EQG PDF Streaming Error: ' . $e->getMessage());
            wp_die(__('Failed to download PDF.', 'eqg'));
        }
    }
    
    private static function get_pdf_template($quote_id, $data) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: 'DejaVu Sans', sans-serif;
                    color: #333;
                    margin: 40px;
                }
                h1 { 
                    color: <?php echo esc_attr(get_theme_mod('primary_color', '#000')); ?>; 
                    font-size: 32px;
                    margin-bottom: 10px;
                }
                .header {
                    border-bottom: 3px solid <?php echo esc_attr(get_theme_mod('primary_color', '#000')); ?>;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .company-info {
                    font-size: 12px;
                    color: #666;
                }
                .quote-info {
                    background: #f9f9f9;
                    padding: 15px;
                    margin-bottom: 30px;
                }
                .quote-info table {
                    width: 100%;
                }
                .quote-info td {
                    padding: 5px;
                }
                .quote-info strong {
                    display: inline-block;
                    width: 100px;
                }
                .services {
                    margin: 30px 0;
                }
                .services table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .services th,
                .services td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                .services th {
                    background: #f4f4f4;
                    font-weight: bold;
                }
                .total { 
                    font-size: 20px; 
                    font-weight: bold;
                    text-align: right;
                    margin-top: 20px;
                    padding: 15px;
                    background: #f4f4f4;
                }
                .footer {
                    margin-top: 50px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                    font-size: 11px;
                    color: #666;
                    text-align: center;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php _e('QUOTATION', 'eqg'); ?></h1>
                <div class="company-info">
                    <strong><?php echo esc_html(get_bloginfo('name')); ?></strong><br>
                    <?php echo esc_html(get_option('admin_email')); ?>
                </div>
            </div>
            
            <div class="quote-info">
                <table>
                    <tr>
                        <td><strong><?php _e('Quote ID:', 'eqg'); ?></strong> #<?php echo esc_html($quote_id); ?></td>
                        <td style="text-align: right;"><strong><?php _e('Date:', 'eqg'); ?></strong> <?php echo date('F d, Y'); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="quote-info">
                <h3><?php _e('Customer Information', 'eqg'); ?></h3>
                <table>
                    <tr>
                        <td><strong><?php _e('Name:', 'eqg'); ?></strong> <?php echo esc_html($data['name']); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Email:', 'eqg'); ?></strong> <?php echo esc_html($data['email']); ?></td>
                    </tr>
                </table>
            </div>
            
            <div class="services">
                <h3><?php _e('Services', 'eqg'); ?></h3>
                <table>
                    <thead>
                        <tr>
                            <th><?php _e('Description', 'eqg'); ?></th>
                            <th style="text-align: right;"><?php _e('Amount', 'eqg'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo esc_html($data['service']); ?></td>
                            <td style="text-align: right;">₱<?php echo number_format($data['amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="total">
                <?php _e('Total Amount:', 'eqg'); ?> ₱<?php echo number_format($data['amount'], 2); ?>
            </div>
            
            <div class="footer">
                <p><?php _e('Thank you for your business!', 'eqg'); ?></p>
                <p>&copy; <?php echo date('Y'); ?> <?php echo esc_html(get_bloginfo('name')); ?>. <?php _e('All rights reserved.', 'eqg'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}