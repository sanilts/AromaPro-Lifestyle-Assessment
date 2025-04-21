<?php

/**
 * Plugin Name: TegelMonsters Pro
 * Description: An Elementor widget that opens a Google Drive video in a lightbox.
 * Version: 1.0.5
 * Author: Sanil T S
 */
 
 add_filter( 'litespeed_ucss_per_pagetype', '__return_true' );
 add_filter( "litespeed_media_ignore_remote_missing_sizes", "__return_true" );
 
require('lib/allimport.php');
require('lib/product.php');


function tegel_monsters_enqueue_style_and_script() {
    $plugin_url = plugin_dir_url(__FILE__);
    wp_enqueue_style('tegel-monsters-style', $plugin_url . 'css/style.css');
    wp_register_script('tegel-monsters-script', $plugin_url . 'js/script.js', '1.0.0', true);
}

add_action('wp_enqueue_scripts', 'tegel_monsters_enqueue_style_and_script');


// Add custom column to products list
add_filter('manage_product_posts_columns', 'add_move_to_top_column');
function add_move_to_top_column($columns) {
    $columns['move_to_top'] = __('Move to Top', 'woocommerce');
    return $columns;
}

// Add button in the custom column
add_action('manage_product_posts_custom_column', 'add_move_to_top_button', 10, 2);
function add_move_to_top_button($column, $post_id) {
    if ($column === 'move_to_top') {
        $nonce = wp_create_nonce('move_to_top_' . $post_id);
        echo '<button class="button move-to-top-btn" data-post-id="' . esc_attr($post_id) . '" data-nonce="' . esc_attr($nonce) . '">Move to Top</button>';
    }
}

// Add JavaScript to handle button click
add_action('admin_footer', 'move_to_top_js');
function move_to_top_js() {
    if (get_current_screen()->post_type !== 'product') return;
    ?>
    <script>
    jQuery(document).ready(function($) {
        $('.move-to-top-btn').click(function(e) {
            e.preventDefault();
            var button = $(this);
            var postId = button.data('post-id');
            var nonce = button.data('nonce');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'move_product_to_top',
                    post_id: postId,
                    nonce: nonce
                },
                beforeSend: function() {
                    button.prop('disabled', true);
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    }
                }
            });
        });
    });
    </script>
    <?php
}

// Handle AJAX request
add_action('wp_ajax_move_product_to_top', 'move_product_to_top');
function move_product_to_top() {
    if (!check_ajax_referer('move_to_top_' . $_POST['post_id'], 'nonce', false)) {
        wp_send_json_error('Invalid nonce');
    }

    $post_id = intval($_POST['post_id']);
    
    // Get highest menu_order
    global $wpdb;
    // Update all other products menu_order
    $wpdb->query("
        UPDATE {$wpdb->posts} 
        SET menu_order = menu_order + 1 
        WHERE post_type = 'product'
    ");

    // Set this product to menu_order 0
    wp_update_post([
        'ID' => $post_id,
        'menu_order' => 0
    ]);

    wp_send_json_success();
}

// Add custom sorting by menu_order
add_filter('woocommerce_get_catalog_ordering_args', 'add_custom_ordering_args');
function add_custom_ordering_args($args) {
    $args['orderby'] = 'menu_order';
    $args['order'] = 'DESC';
    return $args;
}
