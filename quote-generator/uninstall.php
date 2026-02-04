<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all quotes
$quotes = get_posts([
    'post_type' => 'eqg_quote',
    'posts_per_page' => -1,
    'post_status' => 'any'
]);

foreach ($quotes as $quote) {
    // Delete PDF file
    $pdf_path = get_post_meta($quote->ID, '_eqg_pdf_path', true);
    if ($pdf_path && file_exists($pdf_path)) {
        unlink($pdf_path);
    }
    
    // Delete post and meta
    wp_delete_post($quote->ID, true);
}

// Delete upload directory
$upload_dir = wp_upload_dir();
$pdf_dir = $upload_dir['basedir'] . '/eqg-quotes';
if (is_dir($pdf_dir)) {
    rmdir($pdf_dir);
}

// Delete options if any
delete_option('eqg_settings');