<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Collection_brochure_content_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'collection_brochure_content_tag';
    }

    public function get_title() {
        return esc_html__('Collection Brochure', 'elementor-collection-brochure');
    }

    public function get_group() {
        return ['tegelmonsterspro'];
    }

    public function get_categories() {
        return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
    }

    public function render() {
        global $post;
        $term_collection_id=get_related_id($post->ID, 'collecties'); //get_collection_id
                
        if($term_collection_id){
            $collection_id=$term_collection_id;
        }else{        
            $collection_id = get_post_meta($post->ID, 'collectie', true);
        }
        
             
        $collection_id = get_post_meta($post->ID, 'product_brochures', true);
       
        $content_post = get_post($collection_id);
        $content = $content_post->post_content;

        echo $content;        
    }
}