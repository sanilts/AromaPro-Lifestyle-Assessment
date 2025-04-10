<?php

add_action('pmxi_saved_post', 'add_product_details_to_meta', 10, 3);
function add_product_details_to_meta($post_id, $xml_node, $is_update) {
    // Get the imported post type
    $post_type = get_post_type($post_id);
    
    // Only apply to products
    if ($post_type === 'product') {
        // Get the imported product name
        $imported_product_name = get_the_title($post_id);

        // Check if a product with the same name already exists
        $existing_product = get_page_by_title($imported_product_name, OBJECT, 'product');

        $meta_fields = [
            'tile_size',
            'rectified',
            'color',
            'code_article',
            'ean_13',
            'boxes_pallet',
            'pieces_box',
            'sqm_box',
            'kg_pallet',
            'creation_date',
            'abrasion_pei',
            'antislip_shoes',
            'antislip_barefeet',
            'sqm_pal',
            'kg_box',
            'lenght_pal_cm',
            'width_pal_cm',
            'height_pal_cm',
            'stackable_heights',
            'tile_thickness',
            'chromatic_variation',
            'manufature_color'
        ];
        
        $taxonomies = [
            'abrasion-pei',
            'vorm',
            'kleurvariatie',
            'slijtwaarde',
            'afwerking',
            'kleur',
            'size',
            'tegel-type',
            'look',
            'toepassing',
            'gerectificeerd'
        ];
                
        $tegels_meta = array();
        if ($existing_product && $existing_product->ID !== $post_id) {
                       
            wp_set_object_terms($existing_product->ID, get_taxonomy_slug($post_id, 'tegel_size'), 'tegel_size', true);
            
            copy_post_taxonomies($post_id, $existing_product->ID, true );
            
            foreach ($meta_fields as $field) {
                $value = get_post_meta($post_id, $field, true);
                if (!empty($value)) {
                    $tegels_meta[$post_id][$field] = $value;
                    delete_post_meta($post_id, $field);
                }
            }
            $image_id = get_post_thumbnail_id($post_id);
            $image_url = wp_get_attachment_url($image_id);
            $tegels_meta[$post_id]['image'] = $image_url;                                              

            $tegels_meta_value = get_post_meta($existing_product->ID, 'tegels_meta', true);
            if (!empty($tegels_meta_value)) {
                $merged_value = array_merge($tegels_meta_value, $tegels_meta);
                update_post_meta($existing_product->ID, 'tegels_meta', $merged_value);
            }

            wp_delete_post($post_id, true);
        }else{
            create_serie($post_id);
            create_brochure($post_id);
            create_monstermap($post_id);
            foreach ($meta_fields as $field) {
                $value = get_post_meta($post_id, $field, true);
                if (!empty($value)) {
                    $tegels_meta[$post_id][$field] = $value;
                    delete_post_meta($post_id, $field);
                }
            }
            $image_id = get_post_thumbnail_id($post_id);
            $image_url = wp_get_attachment_url($image_id);
            $tegels_meta[$post_id]['image'] = $image_url;
            
            $terms = wp_get_post_terms($post_id, 'tegel_size');
            add_post_meta($post_id, 'tegels_meta', $tegels_meta);
        }
    }
}