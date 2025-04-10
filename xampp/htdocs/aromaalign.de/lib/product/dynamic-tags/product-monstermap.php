<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

class Product_monstermap_content_Tag extends \Elementor\Core\DynamicTags\Tag {

    public function get_name() {
        return 'elementor_monstermap_id_content_tag';
    }

    public function get_title() {
        return esc_html__('MonsterMap ID', 'elementor-monstermap-id');
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
        $term_monstermap_id=get_related_id($post->ID, 'monstermap'); //get_collection_id
                
        if($term_monstermap_id){
            $monstermap_id=$term_monstermap_id;
        }else{        
            $monstermap_id = get_post_meta($post->ID, 'monstermap', true);
        }
        
        echo $monstermap_id;        
    }
}