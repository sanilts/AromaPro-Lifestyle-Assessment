<?php

function create_monstermap($parent_id) {
    $series = get_the_terms($parent_id, 'serie');
    foreach ($series as $serie) {
        $monstermap = $serie->name . ' ' . 'Monstermap';
        $found_post_title = get_page_by_title($monstermap, OBJECT, 'product');
        if (!$found_post_title) {
            $post_data = array(
                'post_title' => $monstermap,
                'post_status' => 'publish',
                'post_type' => 'product',
            );
            $monstermap_id = wp_insert_post($post_data);
            //wp_set_object_terms($monstermap_id, 'give-aways', 'product_cat',);
            wp_set_object_terms($monstermap_id, 'monstermap', 'product_cat',);
            wp_set_object_terms($monstermap_id, $serie->name, 'serie',);

            update_post_meta($parent_id, 'monstermap', $monstermap_id);
            update_post_meta($monstermap_id, '_stock_status', 'outofstock');
        } else {
            update_post_meta($parent_id, 'monstermap', $found_post_title->ID);
        }
    }
}


