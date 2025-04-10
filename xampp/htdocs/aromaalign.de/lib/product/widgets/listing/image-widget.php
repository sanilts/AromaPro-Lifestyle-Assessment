<?php


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Elementor_Dynamic_Image_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'dynamic_image_widget';
    }

    public function get_title() {
        return __('Dynamic Image Widget', 'text-domain');
    }

    public function get_icon() {
        return 'eicon-image';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    protected function register_controls() {
        // Existing Content Section
        $this->start_controls_section(
                'content_section',
                [
                    'label' => __('Content', 'text-domain'),
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'main_image',
                [
                    'label' => __('Main Image', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'dynamic' => [
                        'active' => true,
                    ],
                    'default' => [
                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                    ],
                ]
        );

        $this->add_control(
                'main_image_size',
                [
                    'label' => __('Main Image Size', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'full',
                    'options' => [
                        'thumbnail' => __('Thumbnail', 'text-domain'),
                        'medium' => __('Medium', 'text-domain'),
                        'large' => __('Large', 'text-domain'),
                        'full' => __('Full', 'text-domain'),
                        'custom' => __('Custom', 'text-domain'),
                    ],
                ]
        );

        $this->add_control(
                'main_image_custom_dimension',
                [
                    'label' => __('Main Image Custom Dimension', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::IMAGE_DIMENSIONS,
                    'description' => __('Crop the original image size to any custom size. Set custom width or height to keep the original size ratio.', 'text-domain'),
                    'default' => [
                        'width' => '',
                        'height' => '',
                    ],
                    'condition' => [
                        'main_image_size' => 'custom',
                    ],
                ]
        );

        $this->add_control(
                'hover_image',
                [
                    'label' => __('Hover Image', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::MEDIA,
                    'dynamic' => [
                        'active' => true,
                    ],
                    'default' => [
                        'url' => \Elementor\Utils::get_placeholder_image_src(),
                    ],
                ]
        );

        $this->add_control(
                'hover_image_size',
                [
                    'label' => __('Hover Image Size', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'full',
                    'options' => [
                        'thumbnail' => __('Thumbnail', 'text-domain'),
                        'medium' => __('Medium', 'text-domain'),
                        'large' => __('Large', 'text-domain'),
                        'full' => __('Full', 'text-domain'),
                        'custom' => __('Custom', 'text-domain'),
                    ],
                ]
        );

        $this->add_control(
                'hover_image_custom_dimension',
                [
                    'label' => __('Hover Image Custom Dimension', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::IMAGE_DIMENSIONS,
                    'description' => __('Crop the original image size to any custom size. Set custom width or height to keep the original size ratio.', 'text-domain'),
                    'default' => [
                        'width' => '',
                        'height' => '',
                    ],
                    'condition' => [
                        'hover_image_size' => 'custom',
                    ],
                ]
        );

        $this->add_control(
                'link',
                [
                    'label' => __('Link', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::URL,
                    'placeholder' => __('https://your-link.com', 'text-domain'),
                    'show_external' => true,
                    'default' => [
                        'url' => '',
                        'is_external' => false,
                        'nofollow' => false,
                    ],
                    'dynamic' => [
                        'active' => true,
                    ],
                ]
        );

        $this->end_controls_section();

        $this->start_controls_section(
                'image_style_section',
                [
                    'label' => __('Image Style', 'text-domain'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_responsive_control(
                'image_width',
                [
                    'label' => __('Width', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'vw'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                            'step' => 1,
                        ],
                        '%' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'vw' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => '%',
                        'size' => 100,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'image_max_width',
                [
                    'label' => __('Max Width', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%', 'vw'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                            'step' => 1,
                        ],
                        '%' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                        'vw' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'max-width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'image_height',
                [
                    'label' => __('Height', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', 'vh'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                            'step' => 1,
                        ],
                        'vh' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_control(
                'object_fit',
                [
                    'label' => __('Object Fit', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'cover',
                    'options' => [
                        'fill' => __('Fill', 'text-domain'),
                        'contain' => __('Contain', 'text-domain'),
                        'cover' => __('Cover', 'text-domain'),
                        'none' => __('None', 'text-domain'),
                        'scale-down' => __('Scale Down', 'text-domain'),
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'object-fit: {{VALUE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'image_border',
                    'label' => __('Border', 'text-domain'),
                    'selector' => '{{WRAPPER}} .dynamic-image-widget img',
                ]
        );

        $this->add_responsive_control(
                'image_border_radius',
                [
                    'label' => __('Border Radius', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Box_Shadow::get_type(),
                [
                    'name' => 'image_box_shadow',
                    'label' => __('Box Shadow', 'text-domain'),
                    'selector' => '{{WRAPPER}} .dynamic-image-widget img',
                ]
        );

        $this->end_controls_section();

        // Hover Effects Section
        $this->start_controls_section(
                'hover_effects_section',
                [
                    'label' => __('Hover Effects', 'text-domain'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'hover_animation',
                [
                    'label' => __('Hover Animation', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
                ]
        );

        $this->add_control(
                'hover_transition',
                [
                    'label' => __('Transition Duration', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'default' => [
                        'size' => 0.3,
                    ],
                    'range' => [
                        'px' => [
                            'max' => 3,
                            'step' => 0.1,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'transition-duration: {{SIZE}}s',
                    ],
                ]
        );

        $this->start_controls_tabs('image_hover_effects');

        $this->start_controls_tab(
                'image_hover_normal',
                [
                    'label' => __('Normal', 'text-domain'),
                ]
        );

        $this->add_control(
                'image_opacity',
                [
                    'label' => __('Opacity', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'range' => [
                        'px' => [
                            'max' => 1,
                            'min' => 0.10,
                            'step' => 0.01,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget img' => 'opacity: {{SIZE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Css_Filter::get_type(),
                [
                    'name' => 'image_css_filters',
                    'selector' => '{{WRAPPER}} .dynamic-image-widget img',
                ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
                'image_hover_hover',
                [
                    'label' => __('Hover', 'text-domain'),
                ]
        );

        $this->add_control(
                'image_opacity_hover',
                [
                    'label' => __('Opacity', 'text-domain'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'range' => [
                        'px' => [
                            'max' => 1,
                            'min' => 0.10,
                            'step' => 0.01,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .dynamic-image-widget:hover img' => 'opacity: {{SIZE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Css_Filter::get_type(),
                [
                    'name' => 'image_css_filters_hover',
                    'selector' => '{{WRAPPER}} .dynamic-image-widget:hover img',
                ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        $this->end_controls_section();

        // Tegel Taxonomay Icon View
        $this->start_controls_section(
                'hover_effects_sections',
                [
                    'label' => __('Tegel taxonomay icon position', 'text-domain'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'tegel_attibuite_position',
                [
                    'label' => __('Select Position', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'top-left' => __('Top Left', 'image-hover-widget'),
                        'top-right' => __('Top Right', 'image-hover-widget'),
                        'bottom-left' => __('Bottom Left', 'image-hover-widget'),
                        'bottom-right' => __('Bottom Right', 'image-hover-widget'),
                    ],
                    'default' => 'top-right',
                ]
        );

        $this->add_responsive_control(
                'padding',
                [
                    'label' => __('Padding', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Tegel Size Settings Section 'tab' => \Elementor\Controls_Manager::TAB_STYLE,
        // Tegel Size Settings Section



        $this->start_controls_section(
                'tegel_size_settings',
                [
                    'label' => __('Tegel Size Settings', 'text-domain'),
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_control(
                'tegel_size_text_alignment',
                [
                    'label' => __('Text Alignment', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'left' => [
                            'title' => __('Left', 'plugin-name'),
                            'icon' => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => __('Center', 'plugin-name'),
                            'icon' => 'eicon-text-align-center',
                        ],
                        'right' => [
                            'title' => __('Right', 'plugin-name'),
                            'icon' => 'eicon-text-align-right',
                        ],
                        'justify' => [
                            'title' => __('Justify', 'plugin-name'),
                            'icon' => 'eicon-text-align-justify',
                        ],
                    ],
                    'default' => 'left',
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'text-align: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'tegel_size_text_color',
                [
                    'label' => __('Text Color', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'tegel_size_typography',
                    'label' => __('Typography', 'image-hover-widget'),
                    'selector' => '{{WRAPPER}} .tegel-size-item',
                ]
        );

        $this->add_control(
                'tegel_size_text_stroke',
                [
                    'label' => __('Text Stroke', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 10,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => '-webkit-text-stroke-width: {{SIZE}}{{UNIT}}; text-stroke-width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_control(
                'tegel_size_text_stroke_color',
                [
                    'label' => __('Text Stroke Color', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => '-webkit-text-stroke-color: {{VALUE}}; text-stroke-color: {{VALUE}};',
                    ],
                    'condition' => [
                        'text_stroke[size]!' => '',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Text_Shadow::get_type(),
                [
                    'name' => 'tegel_size_text_shadow',
                    'label' => __('Text Shadow', 'image-hover-widget'),
                    'selector' => '{{WRAPPER}} .tegel-size-item',
                ]
        );

        $this->add_control(
                'tegel_size_position',
                [
                    'label' => __('Position', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'top-left' => __('Top Left', 'image-hover-widget'),
                        'top-right' => __('Top Right', 'image-hover-widget'),
                        'bottom-left' => __('Bottom Left', 'image-hover-widget'),
                        'bottom-right' => __('Bottom Right', 'image-hover-widget'),
                    ],
                    'default' => 'top-right',
                ]
        );

        $this->add_control(
                'tegel_ size_width',
                [
                    'label' => __('Tegel size width', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                        '%' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_control(
                'tegel_ size_height',
                [
                    'label' => __('Tegel size height', 'image-hover-widget'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['px', '%'],
                    'range' => [
                        'px' => [
                            'min' => 0,
                            'max' => 1000,
                        ],
                        '%' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'height: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_control(
                'background_color',
                [
                    'label' => __('Background Color', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'background_opacity',
                [
                    'label' => __('Background Opacity', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::SLIDER,
                    'size_units' => ['%'],
                    'range' => [
                        '%' => [
                            'min' => 0,
                            'max' => 100,
                        ],
                    ],
                    'default' => [
                        'unit' => '%',
                        'size' => 100,
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'opacity: {{SIZE}}%;',
                    ],
                ]
        );

        $this->add_responsive_control(
                'margin',
                [
                    'label' => __('Margin', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'padding',
                [
                    'label' => __('Padding', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_responsive_control(
                'paddingx',
                [
                    'label' => __('Padding', 'plugin-name'),
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%', 'em'],
                    'selectors' => [
                        '{{WRAPPER}} .tegel-size-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();
    }

    protected function render() {

        global $post;
        $settings = $this->get_settings_for_display();

        $link_url = $settings['link']['url'];
        $link_target = $settings['link']['is_external'] ? ' target="_blank"' : '';
        $link_nofollow = $settings['link']['nofollow'] ? ' rel="nofollow"' : '';

        $link_open = $link_url ? '<a href="' . esc_url($link_url) . '"' . $link_target . $link_nofollow . '>' : '';
        $link_close = $link_url ? '</a>' : '';

        // Get the correct image size for main image
        if ($settings['main_image_size'] === 'custom') {
            $main_image_size = [
                $settings['main_image_custom_dimension']['width'],
                $settings['main_image_custom_dimension']['height']
            ];
        } else {
            $main_image_size = $settings['main_image_size'];
        }

        // Get the correct image size for hover image
        if ($settings['hover_image_size'] === 'custom') {
            $hover_image_size = [
                $settings['hover_image_custom_dimension']['width'],
                $settings['hover_image_custom_dimension']['height']
            ];
        } else {
            $hover_image_size = $settings['hover_image_size'];
        }

        $this->add_render_attribute('wrapper', 'class', 'dynamic-image-widget');
        if ($settings['hover_animation']) {
            $this->add_render_attribute('wrapper', 'class', 'elementor-animation-' . $settings['hover_animation']);
        }
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <?php
            echo '<div class="tegel-size ' . $settings['tegel_size_position'] . '";">';
            echo get_tegel_size($post->ID);
            echo '</div>';

            echo '<div class="tegel-attibuite ' . $settings['tegel_attibuite_position'] . '";">';
            echo get_taxonomy_icons($post->ID);
            echo '</div>';
            ?>
            <?php echo $link_open; ?>
            <?php echo wp_get_attachment_image($settings['main_image']['id'], $main_image_size, false, ['class' => 'main-image']); ?>
            <?php echo wp_get_attachment_image($settings['hover_image']['id'], $hover_image_size, false, ['class' => 'hover-image', 'style' => 'display: none;']); ?>
            <?php echo $link_close; ?>            
        </div>
        <script>
            jQuery(document).ready(function ($) {
                $('.dynamic-image-widget').hover(
                        function () {
                            $(this).find('.main-image').hide();
                            $(this).find('.hover-image').show();
                        },
                        function () {
                            $(this).find('.hover-image').hide();
                            $(this).find('.main-image').show();
                        }
                );
            });
        </script>
        <?php
    }
}

function get_tegel_size($post_id) {
    $tegels_meta = get_post_meta($post_id, 'tegels_meta', true);
    $unique_sizes = array();
    $image_sizes = "";

    foreach ($tegels_meta as $tegel) {
        $rectified = ($tegel['rectified'] == 'Ja') ? 'R' : '';
        $size_key = strtolower($tegel['tile_size']).''.$rectified;

        // Only add if this size combination hasn't been seen before
        if (!in_array($size_key, $unique_sizes) && count($unique_sizes) < 8) { // fix the exit issue                    
            $unique_sizes[] = $size_key;        
            $image_sizes .= '<div class="tegel-size-item"><span>' . dotToComma($size_key) . '</span></div>';
        }
    }

    return $image_sizes;
}

function get_taxonomy_icons($post_id) {
    global $product;
    $product_id = $post_id;
    $tegel_icons = array();
    
    // Handle Tegel Types
    $tegel_types = wp_get_post_terms($product_id, 'tegel-type');
    if (is_array($tegel_types)) {
        foreach ($tegel_types as $type) {
            switch ($type->name) {
                case 'Vloertegels':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Vloertegels',
                        'image_name' => 'floor'
                    ]);
                    break;
                case 'Wandtegels':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Wandtegels',
                        'image_name' => 'wall'
                    ]);
                    break;
            }
        }
    }
    
    // Handle Finishes
    $tegel_finishes = wp_get_post_terms($product_id, 'afwerking');
    if (is_array($tegel_finishes)) {
        foreach ($tegel_finishes as $finish) {
            switch ($finish->name) {
                case 'Glanzend':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Glanzend',
                        'image_name' => 'glossy'
                    ]);
                    break;
                case 'Mat':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Mat',
                        'image_name' => 'matte'
                    ]);
                    break;
                case 'Half gepolijst':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Half gepolijst',
                        'image_name' => 'semi-polished'
                    ]);
                    break;
                case 'Gepolijst':
                    $tegel_icons[] = generate_icon_html([
                        'title' => 'Gepolijst',
                        'image_name' => 'polished'
                    ]);
                    break;
            }
        }
    }
    
    // Handle Gerectificeerd
    $gerectificeerds = wp_get_post_terms($product_id, 'gerectificeerd');
    if (is_array($gerectificeerds)) {
        foreach ($gerectificeerds as $gerectificeerd) {
            if ($gerectificeerd->name == 'Ja') {
                $tegel_icons[] = generate_icon_html([
                    'title' => 'Gerectificeerd',
                    'image_name' => 'rectified'
                ]);
            }
        }
    }
    
    // Handle PEI Ratings
    $abrasion_pei = get_post_meta($product_id, 'abrasion_pei', true);
    if ($abrasion_pei) {
        $pei_number = str_replace('PEI ', '', $abrasion_pei);
        if (in_array($pei_number, ['1', '2', '3', '4', '5'])) {
            $tegel_icons[] = generate_icon_html([
                'title' => $abrasion_pei,
                'image_name' => 'pei-' . $pei_number
            ]);
        }
    }
    
    // Handle Antislip Shoes Ratings
    $antislip_shoes = get_post_meta($product_id, 'antislip_shoes', true);
    if ($antislip_shoes && in_array($antislip_shoes, ['R9', 'R10', 'R11', 'R12'])) {
        $tegel_icons[] = generate_icon_html([
            'title' => $antislip_shoes,
            'image_name' => 'antislip-shoe-'.strtolower($antislip_shoes)
        ]);
    }
    
    // Handle Antislip Barefeet Ratings
    $antislip_barefeet = get_post_meta($product_id, 'antislip_barefeet', true);
    if ($antislip_barefeet && in_array($antislip_barefeet, ['A', 'B', 'C'])) {
        $tegel_icons[] = generate_icon_html([
            'title' => 'Antislip Barefeet ' . $antislip_barefeet,
            'image_name' => 'barefeet-' . strtolower($antislip_barefeet)
        ]);
    }
    
    // Join all icons and return
    return implode('', $tegel_icons);
}
