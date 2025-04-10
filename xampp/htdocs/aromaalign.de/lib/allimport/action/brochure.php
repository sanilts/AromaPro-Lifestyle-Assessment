<?php

function create_brochure($parent_id) {
    $series = get_the_terms($parent_id, 'serie');
    foreach ($series as $serie) {
        $brochure = $serie->name . ' ' . 'Brochure';
        $found_post_title = get_page_by_title($brochure, OBJECT, 'product');
        if (!$found_post_title) {
            $post_data = array(
                'post_title' => $brochure,
                'post_status' => 'publish',
                'post_type' => 'product',
            );
            $brochure_id = wp_insert_post($post_data);
            //wp_set_object_terms($brochure_id, 'give-aways', 'product_cat',);
            wp_set_object_terms($brochure_id, 'brochure', 'product_cat',);
            wp_set_object_terms($brochure_id, $serie->name, 'serie',);

            update_post_meta($parent_id, 'brochure', $brochure_id);
            update_post_meta($brochure_id, '_stock_status', 'outofstock');
        } else {
            update_post_meta($parent_id, 'brochure', $found_post_title->ID);
        }
    }
}
