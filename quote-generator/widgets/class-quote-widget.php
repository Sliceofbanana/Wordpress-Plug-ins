<?php
use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class EQG_Quote_Widget extends Widget_Base {

    public function get_name() {
        return 'eqg_quote';
    }

    public function get_title() {
        return 'Quote Generator';
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function render() {
        echo do_shortcode('[quote_generator]');
    }
}
