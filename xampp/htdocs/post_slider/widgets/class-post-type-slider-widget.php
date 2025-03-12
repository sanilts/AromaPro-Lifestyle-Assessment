<?php
/**
 * Post Type Slider Widget Class
 */
class Post_Type_Slider_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'post_type_slider';
    }

    /**
     * Get widget title.
     *
     * @return string Widget title.
     */
    public function get_title() {
        return __('Post Type Slider', 'custom-elementor-widgets');
    }

    /**
     * Get widget icon.
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'eicon-slider-device';
    }

    /**
     * Get widget categories.
     *
     * @return array Widget categories.
     */
    public function get_categories() {
        return ['custom-elementor-widgets', 'general'];
    }

    /**
     * Get all registered post types.
     *
     * @return array
     */
    private function get_post_types() {
        $post_types = get_post_types([
            'public' => true,
            'show_in_nav_menus' => true,
        ], 'objects');

        $options = [];
        foreach ($post_types as $post_type) {
            $options[$post_type->name] = $post_type->label;
        }

        // Remove media from the list
        unset($options['attachment']);

        return $options;
    }

    /**
     * Register widget controls.
     */
    protected function register_controls() {
        // Content Tab
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Slider Settings', 'custom-elementor-widgets'),
            ]
        );

        $this->add_control(
            'post_type',
            [
                'label' => __('Select Post Type', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => $this->get_post_types(),
                'default' => 'post',
            ]
        );

        $this->add_control(
            'posts_per_page',
            [
                'label' => __('Number of Posts', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 20,
                'step' => 1,
                'default' => 6,
            ]
        );

        $this->add_control(
            'orderby',
            [
                'label' => __('Order By', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'date' => __('Date', 'custom-elementor-widgets'),
                    'title' => __('Title', 'custom-elementor-widgets'),
                    'rand' => __('Random', 'custom-elementor-widgets'),
                ],
                'default' => 'date',
            ]
        );

        $this->add_control(
            'order',
            [
                'label' => __('Order', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'ASC' => __('Ascending', 'custom-elementor-widgets'),
                    'DESC' => __('Descending', 'custom-elementor-widgets'),
                ],
                'default' => 'DESC',
            ]
        );

        $this->end_controls_section();

        // Title Options
        $this->start_controls_section(
            'section_title_options',
            [
                'label' => __('Title Options', 'custom-elementor-widgets'),
            ]
        );

        $this->add_control(
            'show_title',
            [
                'label' => __('Show Title', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'custom-elementor-widgets'),
                'label_off' => __('Hide', 'custom-elementor-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'title_position',
            [
                'label' => __('Title Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'bottom' => __('Bottom (Slide Up)', 'custom-elementor-widgets'),
                    'bottom_visible' => __('Bottom (Always Visible)', 'custom-elementor-widgets'),
                    'middle' => __('Middle', 'custom-elementor-widgets'),
                    'top' => __('Top', 'custom-elementor-widgets'),
                ],
                'default' => 'bottom',
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_style',
            [
                'label' => __('Title Style', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'default' => __('Default', 'custom-elementor-widgets'),
                    'boxed' => __('Boxed', 'custom-elementor-widgets'),
                    'minimal' => __('Minimal', 'custom-elementor-widgets'),
                    'highlighted' => __('Highlighted', 'custom-elementor-widgets'),
                    'bold' => __('Bold', 'custom-elementor-widgets'),
                ],
                'default' => 'default',
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_hover_effect',
            [
                'label' => __('Title Hover Effect', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'none' => __('None', 'custom-elementor-widgets'),
                    'slide-up' => __('Slide Up', 'custom-elementor-widgets'),
                    'fade-in' => __('Fade In', 'custom-elementor-widgets'),
                    'zoom-in' => __('Zoom In', 'custom-elementor-widgets'),
                ],
                'default' => 'slide-up',
                'condition' => [
                    'show_title' => 'yes',
                    'title_position' => 'bottom',
                ],
            ]
        );

        $this->end_controls_section();

        // Slider Options
        $this->start_controls_section(
            'section_slider_options',
            [
                'label' => __('Slider Options', 'custom-elementor-widgets'),
            ]
        );

        $this->add_control(
            'slides_to_show',
            [
                'label' => __('Slides to Show', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1,
                'max' => 10,
                'step' => 1,
                'default' => 3,
            ]
        );

        $this->add_control(
            'autoplay',
            [
                'label' => __('Autoplay', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-elementor-widgets'),
                'label_off' => __('No', 'custom-elementor-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'autoplay_speed',
            [
                'label' => __('Autoplay Speed', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::NUMBER,
                'min' => 1000,
                'max' => 15000,
                'step' => 500,
                'default' => 3000,
                'condition' => [
                    'autoplay' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'infinite',
            [
                'label' => __('Infinite Loop', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'custom-elementor-widgets'),
                'label_off' => __('No', 'custom-elementor-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'show_arrows',
            [
                'label' => __('Show Arrows', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'custom-elementor-widgets'),
                'label_off' => __('Hide', 'custom-elementor-widgets'),
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );
        
        // Arrow Position Controls - Horizontal
        $this->add_control(
            'arrows_horizontal_position',
            [
                'label' => __('Arrows Horizontal Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'default' => __('Default (Outside)', 'custom-elementor-widgets'),
                    'inside' => __('Inside', 'custom-elementor-widgets'),
                    'outside' => __('Far Outside', 'custom-elementor-widgets'),
                    'custom' => __('Custom', 'custom-elementor-widgets'),
                ],
                'default' => 'default',
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        // Custom position settings for horizontal
        $this->add_responsive_control(
            'arrows_custom_position_prev_x',
            [
                'label' => __('Previous Arrow X Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -100,
                        'max' => 200,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => -50,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => -20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev' => 'left: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                    'arrows_horizontal_position' => 'custom',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'arrows_custom_position_next_x',
            [
                'label' => __('Next Arrow X Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -100,
                        'max' => 200,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => -50,
                        'max' => 100,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => -20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-next' => 'right: {{SIZE}}{{UNIT}} !important;',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                    'arrows_horizontal_position' => 'custom',
                ],
            ]
        );
        
        // Arrow Position Controls - Vertical
        $this->add_control(
            'arrows_vertical_position',
            [
                'label' => __('Arrows Vertical Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => [
                    'center' => __('Middle (Default)', 'custom-elementor-widgets'),
                    'top' => __('Top', 'custom-elementor-widgets'),
                    'bottom' => __('Bottom', 'custom-elementor-widgets'),
                    'custom' => __('Custom', 'custom-elementor-widgets'),
                ],
                'default' => 'center',
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        // Custom position settings for vertical
        $this->add_responsive_control(
            'arrows_custom_position_y',
            [
                'label' => __('Arrows Y Position', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => -100,
                        'max' => 500,
                        'step' => 1,
                    ],
                    '%' => [
                        'min' => -20,
                        'max' => 120,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 50,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev, {{WRAPPER}} .pts-post-type-slider .slick-next' => 'top: {{SIZE}}{{UNIT}} !important; transform: translateY(-50%) !important;',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                    'arrows_vertical_position' => 'custom',
                ],
            ]
        );
        
        // Previous and Next Arrow Icons
        $this->add_control(
            'prev_arrow_icon',
            [
                'label' => __('Previous Arrow Icon', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'fa4compatibility' => 'arrow_prev_icon',
                'default' => [
                    'value' => 'fas fa-chevron-left',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'arrow-left',
                        'chevron-left',
                        'angle-left',
                        'caret-left',
                        'long-arrow-alt-left',
                    ],
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'next_arrow_icon',
            [
                'label' => __('Next Arrow Icon', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'fa4compatibility' => 'arrow_next_icon',
                'default' => [
                    'value' => 'fas fa-chevron-right',
                    'library' => 'fa-solid',
                ],
                'recommended' => [
                    'fa-solid' => [
                        'arrow-right',
                        'chevron-right',
                        'angle-right',
                        'caret-right',
                        'long-arrow-alt-right',
                    ],
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_dots',
            [
                'label' => __('Show Dots', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Show', 'custom-elementor-widgets'),
                'label_off' => __('Hide', 'custom-elementor-widgets'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->end_controls_section();

        // Style Tab
        $this->start_controls_section(
            'section_style',
            [
                'label' => __('Slider Style', 'custom-elementor-widgets'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'image_height',
            [
                'label' => __('Image Height', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 100,
                        'max' => 800,
                        'step' => 10,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-slider-image-wrapper' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_control(
            'image_border_radius',
            [
                'label' => __('Image Border Radius', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 5,
                    'right' => 5,
                    'bottom' => 5,
                    'left' => 5,
                    'unit' => 'px',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-slider-image-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} .pts-slider-image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => __('Title Color', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .pts-slider-title' => 'color: {{VALUE}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'label' => __('Title Typography', 'custom-elementor-widgets'),
                'selector' => '{{WRAPPER}} .pts-slider-title',
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'title_bg_color',
            [
                'label' => __('Title Background Color', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.7)',
                'selectors' => [
                    '{{WRAPPER}} .pts-slider-title-wrapper' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'title_padding',
            [
                'label' => __('Title Padding', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'default' => [
                    'top' => 15,
                    'right' => 20,
                    'bottom' => 15,
                    'left' => 20,
                    'unit' => 'px',
                    'isLinked' => false,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-slider-title-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_title' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'title_border',
                'label' => __('Title Border', 'custom-elementor-widgets'),
                'selector' => '{{WRAPPER}} .pts-slider-title-wrapper',
                'condition' => [
                    'show_title' => 'yes',
                    'title_style' => ['boxed', 'highlighted'],
                ],
            ]
        );
        
        $this->add_control(
            'arrow_color',
            [
                'label' => __('Arrow Color', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev i, {{WRAPPER}} .pts-post-type-slider .slick-next i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev svg, {{WRAPPER}} .pts-post-type-slider .slick-next svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'arrow_bg_color',
            [
                'label' => __('Arrow Background', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => 'rgba(0, 0, 0, 0.5)',
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev, {{WRAPPER}} .pts-post-type-slider .slick-next' => 'background-color: {{VALUE}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'arrow_size',
            [
                'label' => __('Arrow Size', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 10,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 20,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev i, {{WRAPPER}} .pts-post-type-slider .slick-next i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev svg, {{WRAPPER}} .pts-post-type-slider .slick-next svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'arrow_padding',
            [
                'label' => __('Arrow Padding', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                        'step' => 1,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev, {{WRAPPER}} .pts-post-type-slider .slick-next' => 'padding: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'arrow_border',
                'label' => __('Arrow Border', 'custom-elementor-widgets'),
                'selector' => '{{WRAPPER}} .pts-post-type-slider .slick-prev, {{WRAPPER}} .pts-post-type-slider .slick-next',
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );
        
        $this->add_control(
            'arrow_border_radius',
            [
                'label' => __('Arrow Border Radius', 'custom-elementor-widgets'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 50,
                    'right' => 50,
                    'bottom' => 50,
                    'left' => 50,
                    'unit' => '%',
                    'isLinked' => true,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pts-post-type-slider .slick-prev, {{WRAPPER}} .pts-post-type-slider .slick-next' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'condition' => [
                    'show_arrows' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render() {
        $settings = $this->get_settings_for_display();

        // Query arguments
        $args = [
            'post_type' => $settings['post_type'],
            'posts_per_page' => $settings['posts_per_page'],
            'orderby' => $settings['orderby'],
            'order' => $settings['order'],
        ];

        $query = new \WP_Query($args);

        if ($query->have_posts()) :
            $slider_id = 'pts-slider-' . $this->get_id();
            
            // Get the previous arrow icon HTML
            $prev_arrow_icon_html = '';
            if (!empty($settings['prev_arrow_icon']['value'])) {
                ob_start();
                \Elementor\Icons_Manager::render_icon($settings['prev_arrow_icon'], ['aria-hidden' => 'true']);
                $prev_arrow_icon_html = ob_get_clean();
            } else {
                $prev_arrow_icon_html = '<i class="fas fa-chevron-left"></i>';
            }
            
            // Get the next arrow icon HTML
            $next_arrow_icon_html = '';
            if (!empty($settings['next_arrow_icon']['value'])) {
                ob_start();
                \Elementor\Icons_Manager::render_icon($settings['next_arrow_icon'], ['aria-hidden' => 'true']);
                $next_arrow_icon_html = ob_get_clean();
            } else {
                $next_arrow_icon_html = '<i class="fas fa-chevron-right"></i>';
            }
            
            // Title position and style classes
            $title_position_class = '';
            $title_style_class = '';
            $title_hover_class = '';
            
            // Position class
            if ($settings['show_title'] === 'yes') {
                switch ($settings['title_position']) {
                    case 'top':
                        $title_position_class = 'pts-title-position-top';
                        break;
                    case 'middle':
                        $title_position_class = 'pts-title-position-middle';
                        break;
                    case 'bottom_visible':
                        $title_position_class = 'pts-title-position-bottom pts-title-always-visible';
                        break;
                    default: // bottom with slide-up effect
                        $title_position_class = 'pts-title-position-bottom';
                }
                
                // Style class
                switch ($settings['title_style']) {
                    case 'boxed':
                        $title_style_class = 'pts-title-style-boxed';
                        break;
                    case 'minimal':
                        $title_style_class = 'pts-title-style-minimal';
                        break;
                    case 'highlighted':
                        $title_style_class = 'pts-title-style-highlighted';
                        break;
                    case 'bold':
                        $title_style_class = 'pts-title-style-bold';
                        break;
                    default:
                        $title_style_class = 'pts-title-style-default';
                }
                
                // Hover effect
                if ($settings['title_position'] === 'bottom') {
                    switch ($settings['title_hover_effect']) {
                        case 'fade-in':
                            $title_hover_class = 'pts-title-hover-fade-in';
                            break;
                        case 'zoom-in':
                            $title_hover_class = 'pts-title-hover-zoom-in';
                            break;
                        case 'none':
                            $title_hover_class = 'pts-title-hover-none';
                            break;
                        default:
                            $title_hover_class = 'pts-title-hover-slide-up';
                    }
                }
            }
            
            // Generate slider settings for JS
            $slider_settings = [
                'slidesToShow' => intval($settings['slides_to_show']),
                'autoplay' => ($settings['autoplay'] === 'yes'),
                'autoplaySpeed' => intval($settings['autoplay_speed']),
                'infinite' => ($settings['infinite'] === 'yes'),
                'arrows' => ($settings['show_arrows'] === 'yes'),
                'dots' => ($settings['show_dots'] === 'yes'),
                'prevArrow' => '<button type="button" class="slick-prev">' . $prev_arrow_icon_html . '</button>',
                'nextArrow' => '<button type="button" class="slick-next">' . $next_arrow_icon_html . '</button>',
                'responsive' => [
                    [
                        'breakpoint' => 1024,
                        'settings' => [
                            'slidesToShow' => min(intval($settings['slides_to_show']), 2),
                        ]
                    ],
                    [
                        'breakpoint' => 768,
                        'settings' => [
                            'slidesToShow' => 1,
                        ]
                    ]
                ]
            ];
            
            // Arrows position class
            $arrows_position_class = '';
            
            // Horizontal position
            if ($settings['show_arrows'] === 'yes') {
                switch ($settings['arrows_horizontal_position']) {
                    case 'inside':
                        $arrows_position_class .= ' pts-arrows-horizontal-inside';
                        break;
                    case 'outside':
                        $arrows_position_class .= ' pts-arrows-horizontal-outside';
                        break;
                    case 'custom':
                        $arrows_position_class .= ' pts-arrows-horizontal-custom';
                        break;
                    default:
                        $arrows_position_class .= ' pts-arrows-horizontal-default';
                }
                
                // Vertical position
                switch ($settings['arrows_vertical_position']) {
                    case 'top':
                        $arrows_position_class .= ' pts-arrows-vertical-top';
                        break;
                    case 'bottom':
                        $arrows_position_class .= ' pts-arrows-vertical-bottom';
                        break;
                    case 'custom':
                        $arrows_position_class .= ' pts-arrows-vertical-custom';
                        break;
                    default:
                        $arrows_position_class .= ' pts-arrows-vertical-center';
                }
            }
            ?>
            <div class="pts-slider-container<?php echo esc_attr($arrows_position_class); ?>">
                <div id="<?php echo esc_attr($slider_id); ?>" 
                     class="pts-post-type-slider"
                     data-settings='<?php echo esc_attr(json_encode($slider_settings)); ?>'>
                    <?php while ($query->have_posts()) : $query->the_post(); ?>
                        <div class="pts-slider-item">
                            <div class="pts-slider-image-wrapper">
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="pts-slider-image grayscale">
                                        <?php the_post_thumbnail('large'); ?>
                                    </div>
                                    <div class="pts-slider-image color">
                                        <?php the_post_thumbnail('large'); ?>
                                    </div>
                                <?php else : ?>
                                    <div class="pts-slider-no-image"></div>
                                <?php endif; ?>
                                
                                <?php if ($settings['show_title'] === 'yes') : ?>
                                    <div class="pts-slider-title-wrapper <?php echo esc_attr($title_position_class . ' ' . $title_style_class . ' ' . $title_hover_class); ?>">
                                        <h3 class="pts-slider-title"><?php the_title(); ?></h3>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <style>
            /* Arrow Positions - Horizontal */
            #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-prev,
            #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-next {
                z-index: 100 !important;
            }
            
            .pts-arrows-horizontal-inside #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-prev {
                left: 10px !important;
            }
            
            .pts-arrows-horizontal-inside #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-next {
                right: 10px !important;
            }
            
            .pts-arrows-horizontal-outside #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-prev {
                left: -40px !important;
            }
            
            .pts-arrows-horizontal-outside #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-next {
                right: -40px !important;
            }
            
            /* Arrow Positions - Vertical */
            .pts-arrows-vertical-top #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-prev,
            .pts-arrows-vertical-top #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-next {
                top: 20px !important;
                transform: translateY(0) !important;
            }
            
            .pts-arrows-vertical-bottom #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-prev,
            .pts-arrows-vertical-bottom #<?php echo esc_attr($slider_id); ?>.pts-post-type-slider .slick-next {
                top: auto !important;
                bottom: 20px !important;
                transform: translateY(0) !important;
            }
            
            /* Title styles and positions (remaining the same) */
            /* Title positions */
            #<?php echo esc_attr($slider_id); ?> .pts-title-position-top {
                top: 0;
                bottom: auto;
                transform: translateY(-100%);
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-position-middle {
                top: 50%;
                bottom: auto;
                transform: translateY(-50%);
                text-align: center;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-always-visible {
                transform: translateY(0) !important;
            }
            
            /* Title styles */
            #<?php echo esc_attr($slider_id); ?> .pts-title-style-boxed {
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-style-minimal {
                background-color: transparent !important;
                text-shadow: 1px 1px 3px rgba(0,0,0,0.7);
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-style-highlighted .pts-slider-title {
                display: inline;
                padding: 5px 10px;
                box-decoration-break: clone;
                -webkit-box-decoration-break: clone;
                background-color: <?php echo esc_attr($settings['title_bg_color']); ?>;
                box-shadow: 10px 0 0 <?php echo esc_attr($settings['title_bg_color']); ?>, -10px 0 0 <?php echo esc_attr($settings['title_bg_color']); ?>;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-style-highlighted {
                background-color: transparent !important;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-style-bold .pts-slider-title {
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            /* Hover effects */
            #<?php echo esc_attr($slider_id); ?> .pts-title-hover-fade-in {
                opacity: 0;
                transform: translateY(0);
                transition: opacity 0.4s ease-in-out;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-slider-item:hover .pts-title-hover-fade-in {
                opacity: 1;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-hover-zoom-in {
                transform: translateY(0) scale(0.8);
                opacity: 0;
                transition: transform 0.4s ease-in-out, opacity 0.4s ease-in-out;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-slider-item:hover .pts-title-hover-zoom-in {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
            
            #<?php echo esc_attr($slider_id); ?> .pts-title-hover-none {
                transform: translateY(0);
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                setTimeout(function() {
                    var $slider = $('#<?php echo esc_attr($slider_id); ?>');
                    var settings = $slider.data('settings') || {};
                    
                    // Force unslick if already initialized
                    if ($slider.hasClass('slick-initialized')) {
                        $slider.slick('unslick');
                    }
                    
                    // Initialize slider
                    try {
                        $slider.slick(settings);
                        console.log('Slider initialized successfully');
                    } catch(e) {
                        console.error('Error initializing slider:', e);
                    }
                }, 500);
            });
            </script>
            <?php
        endif;

        wp_reset_postdata();
    }
}