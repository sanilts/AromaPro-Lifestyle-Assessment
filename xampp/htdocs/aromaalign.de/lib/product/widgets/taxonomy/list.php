<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Elementor widget for displaying taxonomy lists with special handling for serie taxonomy.
 */
class Tegel_Taxomony_List extends \Elementor\Widget_Base {
    public function get_name() {
        return 'taxonomy_list';
    }

    public function get_title() {
        return esc_html__('Tegel Taxonomy List', 'textdomain');
    }

    public function get_icon() {
        return 'eicon-gallery-grid';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    public function get_keywords() {
        return ['taxonomy', 'tiles', 'attributes'];
    }

    private function get_available_taxonomies() {
        $taxonomies = get_object_taxonomies('product', 'objects');
        $options = [];
        foreach ($taxonomies as $taxonomy) {
            $options[$taxonomy->name] = $taxonomy->label;
        }
        return $options;
    }

    protected function register_controls() {
        // Layout Section
        $this->start_controls_section(
            'section_layout',
            [
                'label' => esc_html__('Layout', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_responsive_control(
            'columns',
            [
                'label' => esc_html__('Columns', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => '2',
                'options' => [
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '5' => '5',
                    '6' => '6',
                ],
                'prefix_class' => 'elementor-grid%s-',
            ]
        );

        $this->add_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'textdomain'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'textdomain'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'textdomain'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'textdomain'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .tegel-item' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'item_gap',
            [
                'label' => esc_html__('Items Gap', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'default' => [
                    'size' => 20,
                ],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}}' => '--grid-row-gap: {{SIZE}}{{UNIT}}; --grid-column-gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Content Section
        $this->start_controls_section(
            'section_content',
            [
                'label' => esc_html__('Content', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $repeater = new \Elementor\Repeater();

        $repeater->add_control(
            'taxonomy',
            [
                'label' => esc_html__('Select Taxonomy', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_available_taxonomies(),
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'custom_label',
            [
                'label' => esc_html__('Custom Label', 'textdomain'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'label_block' => true,
            ]
        );

        $repeater->add_control(
            'enable_link',
            [
                'label' => esc_html__('Enable Link', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => 'no',
                'description' => esc_html__('Note: Serie taxonomy will always link to collection products.', 'textdomain'),
            ]
        );

        $this->add_control(
            'taxonomies',
            [
                'label' => esc_html__('Taxonomies', 'textdomain'),
                'type' => \Elementor\Controls_Manager::REPEATER,
                'fields' => $repeater->get_controls(),
                'title_field' => '{{{ custom_label || taxonomy }}}',
            ]
        );

        $this->end_controls_section();

        // Style Section - Items
        $this->start_controls_section(
            'section_item_style',
            [
                'label' => esc_html__('Items', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'item_background_color',
            [
                'label' => esc_html__('Background Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tegel-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'item_border',
                'selector' => '{{WRAPPER}} .tegel-item',
            ]
        );

        $this->add_responsive_control(
            'item_padding',
            [
                'label' => esc_html__('Padding', 'textdomain'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .tegel-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );
        
        $this->add_responsive_control(
            'item_margin',
            [
                'label' => esc_html__('Margin', 'textdomain'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .tegel-item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'item_border_radius',
            [
                'label' => esc_html__('Border Radius', 'textdomain'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .tegel-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Labels
        $this->start_controls_section(
            'section_label_style',
            [
                'label' => esc_html__('Labels', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'label_typography',
                'selector' => '{{WRAPPER}} .tegel-label',
            ]
        );

        $this->add_control(
            'label_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tegel-label' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'label_spacing',
            [
                'label' => esc_html__('Spacing', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', 'em'],
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .tegel-label' => 'margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Values
        $this->start_controls_section(
            'section_value_style',
            [
                'label' => esc_html__('Values', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'value_typography',
                'selector' => '{{WRAPPER}} .tegel-value',
            ]
        );

        $this->add_control(
            'value_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tegel-value' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section - Links
        $this->start_controls_section(
            'section_links_style',
            [
                'label' => esc_html__('Links', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('links_style_tabs');

        $this->start_controls_tab(
            'links_normal_tab',
            [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tegel-value a' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'link_typography',
                'selector' => '{{WRAPPER}} .tegel-value a',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'links_hover_tab',
            [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'link_hover_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .tegel-value a:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        global $post;
        
        if (!$post) {
            return;
        }

        echo '<div class="elementor-grid">';
        
        foreach ($settings['taxonomies'] as $item) {
            $terms = get_the_terms($post->ID, $item['taxonomy']);
            
            if (!is_wp_error($terms) && !empty($terms)) {
                $term_list = [];
                
                // Special handling for Serie taxonomy
                if ($item['taxonomy'] === 'serie') {
                    $term_list = $this->get_collection_links($terms);
                } else {
                    // Normal taxonomy handling
                    foreach ($terms as $term) {
                        if ($item['enable_link'] === 'yes') {
                            $term_list[] = sprintf(
                                '<a href="%s">%s</a>',
                                esc_url(get_term_link($term)),
                                esc_html($term->name)
                            );
                        } else {
                            $term_list[] = esc_html($term->name);
                        }
                    }
                }

                if (!empty($term_list)) {
                    $label = !empty($item['custom_label']) ? $item['custom_label'] : $item['taxonomy'];
                    
                    printf(
                        '<div class="elementor-grid-item tegel-item">
                            <div class="tegel-label">%s</div>
                            <div class="tegel-value">%s</div>
                        </div>',
                        esc_html($label),
                        wp_kses_post(implode(', ', $term_list))
                    );
                }
            }
        }
        
        echo '</div>';
    }

    /**
     * Get collection product links for series terms
     */
    private function get_collection_links($series_terms) {
        $links = [];

        foreach ($series_terms as $term) {
            $args = [
                'post_type' => 'product',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'tax_query' => [
                    'relation' => 'AND',
                    [
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => 'collecties',
                    ],
                    [
                        'taxonomy' => 'serie',
                        'field' => 'term_id',
                        'terms' => $term->term_id,
                    ],
                ],
            ];

            $collection_query = new WP_Query($args);

            if ($collection_query->have_posts()) {
                foreach ($collection_query->posts as $collection_id) {
                    $links[] = sprintf(
                        '<a href="%s">%s</a>',
                        esc_url(get_permalink($collection_id)),
                        get_the_title($collection_id)
                    );
                }
            }

            wp_reset_postdata();
        }

        return $links;
    }
}

// Register the widget
add_action('elementor/widgets/register', function($widgets_manager) {
    $widgets_manager->register(new Tegel_Taxomony_List());
});

/**
 * Add custom CSS class to handle grid & flex fallback
 */
function add_tegel_taxonomy_widget_css() {
    wp_add_inline_style('elementor-frontend', '
        .elementor-grid {
            display: grid;
        }
        .elementor-grid-item {
            min-width: 0;
        }
        .tegel-item {
            display: flex;
            align-items: baseline;
            gap: 0.5em;
        }
        .tegel-label {
            font-weight: 500;
        }
        .tegel-value {
            flex: 1;
        }
        @media (max-width: 767px) {
            .tegel-item {
                flex-direction: column;
                gap: 0.25em;
            }
        }
    ');
}
add_action('elementor/frontend/after_enqueue_styles', 'add_tegel_taxonomy_widget_css');

/**
 * Add post type support for taxonomies if not already added
 */
function ensure_product_taxonomies() {
    if (!taxonomy_exists('serie')) {
        register_taxonomy('serie', 'product', [
            'label' => __('Serie', 'textdomain'),
            'hierarchical' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'query_var' => true,
        ]);
    }
}
add_action('init', 'ensure_product_taxonomies');