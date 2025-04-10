<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

include('action/collection.php');
include('action/mergetegels.php');
include('action/brochure.php');
include('action/monstermap.php');

// All import action while importing the tegels
function get_taxonomy_slug($id, $prodct_taxonomy) {
    $terms = get_the_terms($id, $prodct_taxonomy, true);
    $term_slug = array();
    foreach ($terms as $term) {
        $term_slug[] = $term->slug;
    }
    return $term_slug;
}

include('action/inc/copy-post-taxonomies.php');
include('action/inc/get-taxonomy-names-by-product-id.php');
