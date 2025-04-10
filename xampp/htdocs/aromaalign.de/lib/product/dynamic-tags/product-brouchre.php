<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Product_brochure_content_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'elementor_brochure_id_content_tag';
    }

    public function get_title() {
        return esc_html__('Brochure ID', 'elementor-brochure-id');
    }

    public function get_group() {
        return ['tegelmonsterspro'];
    }

    public function get_categories() {
        return [
            \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY,
            \Elementor\Modules\DynamicTags\Module::URL_CATEGORY
        ];
    }

    public function render() {
        global $post;
        $term_brochure_id=get_related_id($post->ID, 'brochure'); //get_collection_id
                
        if($term_brochure_id){
            $brochure_id=$term_brochure_id;
        }else{        
            $brochure_id = get_post_meta($post->ID, 'brochure', true);
        }
        
        echo $brochure_id;
    }
}