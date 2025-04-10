<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function copy_post_taxonomies($source_post_id, $target_post_id, $append = false) {
    // Verify posts exist
    if (!get_post($source_post_id) || !get_post($target_post_id)) {
        return new WP_Error('invalid_post', 'Invalid source or target post ID');
    }

    $results = array();

    // Get all taxonomies
    $taxonomies = get_object_taxonomies(get_post_type($source_post_id));

    // Skip default taxonomies if you only want custom ones
    //$default_taxonomies = array('category', 'post_tag', 'post_format');
    //$taxonomies = array_diff($taxonomies, $default_taxonomies);

    foreach ($taxonomies as $taxonomy) {

        if ($taxonomy != 'product_cat') {
            // Get all terms for the source post in this taxonomy
            $terms = wp_get_object_terms($source_post_id, $taxonomy, array('fields' => 'ids'));

            if (!is_wp_error($terms) && !empty($terms)) {
                // Set terms for the target post
                $set_terms = wp_set_object_terms($target_post_id, $terms, $taxonomy, $append);

                if (!is_wp_error($set_terms)) {
                    $results[$taxonomy] = array(
                        'status' => 'success',
                        'terms_copied' => count($terms)
                    );
                } else {
                    $results[$taxonomy] = array(
                        'status' => 'error',
                        'message' => $set_terms->get_error_message()
                    );
                }
            }
        }
    }

    return $results;
}
