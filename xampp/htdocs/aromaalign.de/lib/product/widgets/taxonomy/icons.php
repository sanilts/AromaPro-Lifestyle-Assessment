<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Tegel_Taxonomy_Icons extends \Elementor\Widget_Base {

    public function get_name() {
        return 'taxonomy_icons';
    }

    public function get_title() {
        return esc_html__('Tegel Taxonomy Icons', 'textdomain');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    public function get_keywords() {
        return ['taxonomy', 'icons', 'tiles', 'product', 'tegel'];
    }
    
    protected function render() {
        $settings = $this->get_settings_for_display();
        global $post, $product;

        if (!$post || !$product) {
            return;
        }

        $product_id = $post->ID;
        echo get_taxonomy_icons($product_id);
    }
}