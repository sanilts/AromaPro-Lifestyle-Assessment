<?php
namespace TegelMonstersPro\Elementor\DynamicTags;
use Elementor\Core\DynamicTags\Data_Tag;
use Elementor\Modules\DynamicTags\Module as TagsModule;

class Collection_Image_Gallery_Tag extends Data_Tag {
    public function get_name() {
        return 'collection-image-gallery-tag';
    }

    public function get_title() {
        return __('Collection Image Gallery', 'elementor-dynamic-collection');
    }

    public function get_group() {
        return ['woocommerce'];
    }

    public function get_categories() {
        return [TagsModule::GALLERY_CATEGORY];
    }

    public function get_value(array $options = []) {
        global $post;
        $images = [];

        if (!$post || !$post->ID) {
            return $images;
        }

        // Get collection ID
        $term_collection_id = get_related_id($post->ID, 'collecties'); //get_collection_id
        $collection_id = $term_collection_id ? $term_collection_id : get_post_meta($post->ID, 'collectie', true);

        // Verify we have a valid collection ID
        if (!$collection_id) {
            return $images;
        }

        // Get the product
        $product = wc_get_product($collection_id);
        
        // Check if product exists and is valid
        if (!$product || !is_a($product, 'WC_Product')) {
            return $images;
        }

        // Get gallery images
        $attachment_ids = $product->get_gallery_image_ids();
        
        if (!empty($attachment_ids)) {
            foreach ($attachment_ids as $attachment_id) {
                $url = wp_get_attachment_url($attachment_id);
                if ($url) {
                    $images[] = [
                        'id' => $attachment_id,
                        'url' => $url,
                    ];
                }
            }
        }

        return $images;
    }
}