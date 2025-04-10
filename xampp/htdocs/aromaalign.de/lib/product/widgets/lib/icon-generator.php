<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

// Helper function to get detailed tooltip text
function get_icon_tooltip_text($type, $value) {
    $tooltips = [
        'floor' => 'Vloertegels - Geschikt voor vloeren',
        'wall' => 'Wandtegels - Geschikt voor wanden',
        'glossy' => 'Glanzende afwerking - Hoogglanzend oppervlak',
        'matte' => 'Matte afwerking - Niet-reflecterend oppervlak',
        'semi-polished' => 'Half gepolijst - Licht glanzend oppervlak',
        'polished' => 'Gepolijst - Volledig gepolijst oppervlak',
        'rectified' => 'Gerectificeerd - Precisie gesneden randen',
        'pei-1' => 'PEI 1 - Zeer licht gebruik (wandtegels)',
        'pei-2' => 'PEI 2 - Licht gebruik (badkamers, slaapkamers)',
        'pei-3' => 'PEI 3 - Gemiddeld gebruik (alle woonruimtes)',
        'pei-4' => 'PEI 4 - Zwaar gebruik (alle ruimtes, licht commercieel)',
        'pei-5' => 'PEI 5 - Extra zwaar gebruik (commerciële ruimtes)',
        'antislip-shoe-r9' => 'R9 - Lichte antislip voor droge ruimtes',
        'antislip-shoe-r10' => 'R10 - Gemiddelde antislip voor vochtige ruimtes',
        'antislip-shoe-r11' => 'R11 - Sterke antislip voor natte ruimtes',
        'antislip-shoe-r12' => 'R12 - Extra sterke antislip voor zeer natte ruimtes',
        'barefeet-a' => 'Antislip A - Basis antislip voor blote voeten',
        'barefeet-b' => 'Antislip B - Verbeterde antislip voor blote voeten',
        'barefeet-c' => 'Antislip C - Maximale antislip voor blote voeten'
    ];
    
    return isset($tooltips[$type]) ? $tooltips[$type] : $value;
}

// Updated generate_icon_html function with enhanced tooltips
function generate_icon_html($params) {
    // Default parameters
    $defaults = array(
        'title' => '',
        'image_name' => '',
        'width' => 24,
        'height' => 24,
        'base_path' => plugin_dir_url( __DIR__ ) . 'lib/images/',
        'class' => 'taxonomy-icon',
        'wrapper_class' => 'icon-tooltip-wrapper',
        'tooltip_text' => ''
    );

    // Merge provided parameters with defaults
    $params = wp_parse_args($params, $defaults);

    // Validate required parameters
    if (empty($params['title']) || empty($params['image_name'])) {
        return '';
    }

    // Get detailed tooltip text
    $tooltip_text = !empty($params['tooltip_text']) 
        ? $params['tooltip_text'] 
        : get_icon_tooltip_text($params['image_name'], $params['title']);

    // Generate the HTML
    $html = sprintf(
        '<span class="%s">
            <img src="%s" 
                 alt="%s" 
                 class="%s" 
                 width="%d" 
                 height="%d" />
            <span class="tooltip-text">%s</span>
        </span>',
        esc_attr($params['wrapper_class']),
        esc_url($params['base_path'] . $params['image_name'] . '.png'),
        esc_attr($params['title']),
        esc_attr($params['class']),
        absint($params['width']),
        absint($params['height']),
        esc_html($tooltip_text)
    );

    return $html;
}