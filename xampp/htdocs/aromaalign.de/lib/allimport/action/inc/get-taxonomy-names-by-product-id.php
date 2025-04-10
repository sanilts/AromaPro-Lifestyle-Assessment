<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

function get_taxonomy_names_by_product_id($product_id) {
    $taxonomy_names = array();
    
    // Get all terms associated with the product
    $terms = get_the_terms($product_id, 'serie');    
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            $taxonomy_names=$term->name;            
        }
        return $taxonomy_names;
    }else{
        return 'Test-'.$product_id;
    }        
}