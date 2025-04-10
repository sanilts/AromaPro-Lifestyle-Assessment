<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

function create_serie($product_id) {
    $serie_name = capitalizeWords(get_post_meta($product_id, 'serie_name', true));
    $found_post_title = get_page_by_title($serie_name, OBJECT, 'product');
    if (!$found_post_title) {
        $post_data = array(
            'post_title' => $serie_name,
            'post_status' => 'publish',
            'post_type' => 'product',
        );
        $serie_id = wp_insert_post($post_data);
        wp_set_object_terms($serie_id, 'Collecties', 'product_cat', true);
        copy_post_taxonomies($product_id, $serie_id, 'true');
    }else{               
        copy_post_taxonomies($product_id, $found_post_title->ID, true);
    }
}