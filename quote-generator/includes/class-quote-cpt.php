<?php
if (!defined('ABSPATH')) exit;

add_action('init', function () {
    register_post_type('eqg_quote', [
        'label' => __('Quotations', 'eqg'),
        'labels' => [
            'name' => __('Quotations', 'eqg'),
            'singular_name' => __('Quotation', 'eqg'),
            'add_new' => __('Add New', 'eqg'),
            'add_new_item' => __('Add New Quotation', 'eqg'),
            'edit_item' => __('Edit Quotation', 'eqg'),
            'view_item' => __('View Quotation', 'eqg'),
            'search_items' => __('Search Quotations', 'eqg'),
            'not_found' => __('No quotations found', 'eqg'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'capability_type' => 'post',
        'capabilities' => [
            'create_posts' => false,
        ],
        'map_meta_cap' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-media-document',
        'menu_position' => 25,
    ]);
});