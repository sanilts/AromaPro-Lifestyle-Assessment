<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

function register_site_dynamic_tag_group($dynamic_tags_manager) {
    $dynamic_tags_manager->register_group(
            'tegelmonsterspro',
            [
                'title' => esc_html__('Tegel Monsters Pro', 'elementor-acf-average-dynamic-tag')
            ]
    );
}

add_action('elementor/dynamic_tags/register', 'register_site_dynamic_tag_group');

function register_tegel_monsterspro_dynamic_tag($dynamic_tags_manager) {
    require_once( __DIR__ . '/dynamic-tags/collection-content.php' );
    require_once( __DIR__ . '/dynamic-tags/collection-gallery.php' );
    require_once( __DIR__ . '/dynamic-tags/collection-image.php' );
    require_once( __DIR__ . '/dynamic-tags/product-brouchre.php' );
    require_once( __DIR__ . '/dynamic-tags/product-monstermap.php' );
    $dynamic_tags_manager->register(new Collection_post_content_Tag());
    $dynamic_tags_manager->register(new \TegelMonstersPro\Elementor\DynamicTags\Collection_Image_Gallery_Tag());
    $dynamic_tags_manager->register(new Collection_Featured_Image_Tag());
    $dynamic_tags_manager->register(new Product_brochure_content_Tag());
    $dynamic_tags_manager->register(new Product_monstermap_content_Tag());
}

add_action('elementor/dynamic_tags/register', 'register_tegel_monsterspro_dynamic_tag');

/**
 * Get the associated collection product ID for a given product.
 * 
 * @param int $product_id The ID of the product to find the collection for.
 * @return int|string Returns the collection product ID if found, empty string otherwise.
 */
function get_related_id($product_id, $cat) {
    
    // Get the 'serie' terms for the product
    $tegel_series = wp_get_post_terms($product_id, 'serie', [
        'fields' => 'ids',
        'suppress_filters' => false
    ]);

    if (empty($tegel_series)) {
        $related_id[$product_id] = '';
        return '';
    }

    // Query for a matching collection product
    $args = [
        'post_type' => 'product',
        'tax_query' => [
            'relation' => 'AND',
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => $cat,
            ],
            [
                'taxonomy' => 'serie',
                'field' => 'term_id',
                'terms' => $tegel_series,
            ],
        ],
        'posts_per_page' => 1,
        'fields' => 'ids', // Only get post IDs for better performance
        'no_found_rows' => true, // Improves performance when pagination not needed
    ];

    $collection_query = new WP_Query($args);

    $result = '';
    if ($collection_query->have_posts()) {
        $result = $collection_query->posts[0];
    }

    return $result;
}
