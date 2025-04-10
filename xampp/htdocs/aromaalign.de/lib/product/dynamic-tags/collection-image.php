<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Collection_Featured_Image_Tag extends \Elementor\Core\DynamicTags\Data_Tag {

    public function get_name() {
        return 'collection-featured-image';
    }

    public function get_title() {
        return __('Collection Featured Image', 'elementor-dynamic-collection');
    }

    public function get_group() {
        return 'media';
    }

    public function get_categories() {
        return ['image'];
    }

    protected function register_controls() {
        $this->add_control(
            'fallback_image',
            [
                'label' => __('Fallback Image', 'elementor-dynamic-collection'),
                'type' => \Elementor\Controls_Manager::MEDIA,
                'default' => [
                    'url' => \Elementor\Utils::get_placeholder_image_src(),
                ],
            ]
        );
    }

    protected function get_value(array $options = []) {
        $post_id = get_the_ID();
        
        $term_collection_id=get_related_id($post_id, 'collecties'); //get_collection_id        
        
        if($term_collection_id){
            $collection_id=$term_collection_id;
        }else{        
            $collection_id = get_post_meta($post_id, 'collectie', true);
        }
        
        // Try to get the featured image
        if (has_post_thumbnail($collection_id)) {
            $image_id = get_post_thumbnail_id($collection_id);
        } else {
            // Use the fallback image set in the UI
            $fallback_image = $this->get_settings('fallback_image');
            $image_id = $fallback_image['id'];
        }
        
        $image_data = [
            'id' => $image_id,
            'url' => wp_get_attachment_image_src($image_id, 'full')[0],
        ];
        
        return $image_data;
    }
}