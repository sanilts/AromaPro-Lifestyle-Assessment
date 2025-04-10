<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Elementor_PDF_Handler_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'pdf-handler';
    }

    public function get_title() {
        return __('PDF Handler', 'plugin-name');
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'preview_icon',
            [
                'label' => esc_html__('Preview Icon', 'textdomain'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-eye',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'download_icon',
            [
                'label' => esc_html__('Download Icon', 'textdomain'),
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-download',
                    'library' => 'fa-solid',
                ],
            ]
        );

        $this->add_control(
            'display_mode',
            [
                'label' => esc_html__('Display Mode', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'both',
                'options' => [
                    'both' => esc_html__('Preview & Download', 'textdomain'),
                    'preview' => esc_html__('Preview Only', 'textdomain'),
                    'download' => esc_html__('Download Only', 'textdomain'),
                ],
            ]
        );

        $this->add_control(
            'vertical_alignment',
            [
                'label' => esc_html__('Vertical Alignment', 'textdomain'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Top', 'textdomain'),
                        'icon' => 'eicon-v-align-top',
                    ],
                    'center' => [
                        'title' => esc_html__('Middle', 'textdomain'),
                        'icon' => 'eicon-v-align-middle',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Bottom', 'textdomain'),
                        'icon' => 'eicon-v-align-bottom',
                    ],
                ],
                'default' => 'center',
                'selectors' => [
                    '{{WRAPPER}} .pdf-handler-item' => 'align-items: {{VALUE}};',
                    '{{WRAPPER}} .pdf-handler-item a' => 'align-items: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'horizontal_alignment',
            [
                'label' => esc_html__('Horizontal Alignment', 'textdomain'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'flex-start' => [
                        'title' => esc_html__('Left', 'textdomain'),
                        'icon' => 'eicon-h-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'textdomain'),
                        'icon' => 'eicon-h-align-center',
                    ],
                    'flex-end' => [
                        'title' => esc_html__('Right', 'textdomain'),
                        'icon' => 'eicon-h-align-right',
                    ],
                ],
                'default' => 'flex-start',
                'selectors' => [
                    '{{WRAPPER}} .pdf-handler-wrapper' => 'justify-content: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Icon Style Section
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => esc_html__('Icon Style', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'icon_size',
            [
                'label' => esc_html__('Icon Size', 'textdomain'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px'],
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 300,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 16,
                ],
                'selectors' => [
                    '{{WRAPPER}} .pdf-action i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .pdf-action svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->start_controls_tabs('icon_colors');

        $this->start_controls_tab(
            'icon_colors_normal',
            [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => esc_html__('Icon Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '',
                'selectors' => [
                    '{{WRAPPER}} .pdf-action i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pdf-action svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'icon_colors_hover',
            [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'icon_hover_color',
            [
                'label' => esc_html__('Icon Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pdf-action:hover i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .pdf-action:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();

        // Button Style Section
        $this->start_controls_section(
            'button_style_section',
            [
                'label' => esc_html__('Button Style', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->start_controls_tabs('button_styles');

        $this->start_controls_tab(
            'button_normal',
            [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'button_background',
            [
                'label' => esc_html__('Background Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#f5f5f5',
                'selectors' => [
                    '{{WRAPPER}} .pdf-action' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => esc_html__('Text Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .pdf-action' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .pdf-action',
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'button_hover',
            [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'button_background_hover',
            [
                'label' => esc_html__('Background Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#e5e5e5',
                'selectors' => [
                    '{{WRAPPER}} .pdf-action:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_text_color_hover',
            [
                'label' => esc_html__('Text Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pdf-action:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border_hover',
                'selector' => '{{WRAPPER}} .pdf-action:hover',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'button_border_radius',
            [
                'label' => esc_html__('Border Radius', 'textdomain'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'default' => [
                    'top' => 4,
                    'right' => 4,
                    'bottom' => 4,
                    'left' => 4,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .pdf-action' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'button_padding',
            [
                'label' => esc_html__('Padding', 'textdomain'),
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em'],
                'default' => [
                    'top' => 8,
                    'right' => 16,
                    'bottom' => 8,
                    'left' => 16,
                    'unit' => 'px',
                ],
                'selectors' => [
                    '{{WRAPPER}} .pdf-action' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Title Style Section
        $this->start_controls_section(
            'title_style_section',
            [
                'label' => esc_html__('Title Style', 'textdomain'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'title_typography',
                'selector' => '{{WRAPPER}} .pdf-title',
            ]
        );

        $this->start_controls_tabs('title_colors');

        $this->start_controls_tab(
            'title_colors_normal',
            [
                'label' => esc_html__('Normal', 'textdomain'),
            ]
        );

        $this->add_control(
            'title_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#333333',
                'selectors' => [
                    '{{WRAPPER}} .pdf-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'title_colors_hover',
            [
                'label' => esc_html__('Hover', 'textdomain'),
            ]
        );

        $this->add_control(
            'title_hover_color',
            [
                'label' => esc_html__('Color', 'textdomain'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .pdf-title:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    protected function render() {
        global $post;
        $settings = $this->get_settings_for_display();
        
        if (!$post || !is_object($post)) {
            return;
        }

        $term_collection_id = $this->get_collection_id($post->ID);
        $collection_id = $term_collection_id ? $term_collection_id : get_post_meta($post->ID, 'collectie', true);

        if (!$collection_id) {
            return;
        }

        // Get both types of documents
        $brochures = get_post_meta($collection_id, 'product_brochures', true);
        $monstermap = get_post_meta($collection_id, 'monster_map', true);
        $technical_specs = get_post_meta($collection_id, 'technical-specs', true);

        // Combine documents into a single array with type indicator
        $documents = [];
        
        if (is_array($brochures)) {
            foreach ($brochures as $doc) {
                if (isset($doc['upload-brochure-pdf']) && isset($doc['brochure-name'])) {
                    $documents[] = [
                        'url' => $doc['upload-brochure-pdf'],
                        'name' => $doc['brochure-name'],
                        'type' => 'brochure'
                    ];
                }
            }
        }
        
        if (is_array($monstermap)) {
            foreach ($monstermap as $doc) {
                if (isset($doc['url']) && isset($doc['name'])) {
                    $documents[] = [
                        'url' => $doc['url'],
                        'name' => $doc['name'],
                        'type' => 'monstermap'
                    ];
                }
            }
        }

        if (is_array($technical_specs)) {
            foreach ($technical_specs as $doc) {
                if (isset($doc['tech_specs_link_to_file_url']) && isset($doc['tech_specs_name'])) {
                    $documents[] = [
                        'url' => $doc['tech_specs_link_to_file_url'],
                        'name' => $doc['tech_specs_name'],
                        'type' => 'technical'
                    ];
                }
            }
        }

        if (empty($documents)) {
            return;
        }

        ?>
        <style>
            .pdf-handler-wrapper {
                display: flex;
                width: 100%;
                flex-wrap: wrap;
                gap: 20px;
            }
            .pdf-handler-item {
                display: flex;
                flex-direction: column;
                gap: 10px;
                min-width: 200px;
            }
            .pdf-actions {
                display: flex;
                gap: 10px;
            }
            .pdf-action {
                display: inline-flex;
                align-items: center;
                text-decoration: none;
                padding: 8px 16px;
                border-radius: 4px;
                transition: all 0.3s ease;
                background: #f5f5f5;
                color: #333;
                font-size: 14px;
                line-height: 1.4;
            }
            .pdf-action:hover {
                background: #e5e5e5;
            }
            .pdf-action i, 
            .pdf-action svg {
                margin-right: 8px;
            }
            .pdf-title {
                margin: 5px 0;
                font-weight: 500;
                color: #333;
                font-size: 16px;
            }
            .preview-action {
                background: #f0f7ff;
                color: #0066cc;
            }
            .preview-action:hover {
                background: #e0f0ff;
            }
            .download-action {
                background: #f0fff4;
                color: #00994d;
            }
            .download-action:hover {
                background: #e0ffe8;
            }
        </style>

        <div class="pdf-handler-wrapper">
            <?php foreach ($documents as $doc) : ?>
                <div class="pdf-handler-item">
                    <div class="pdf-title">
                        <?php echo esc_html($doc['name']); ?>
                        <span class="pdf-type">
                            <?php echo esc_html(ucfirst($doc['type'])); ?>
                        </span>
                    </div>
                    
                    <div class="pdf-actions">
                        <?php if ($settings['display_mode'] === 'both' || $settings['display_mode'] === 'preview') : ?>
                            <a href="<?php echo esc_url($doc['url']); ?>" 
                               class="pdf-action preview-action" 
                               target="_blank" 
                               rel="noopener noreferrer">
                                <?php \Elementor\Icons_Manager::render_icon($settings['preview_icon'], ['aria-hidden' => 'true']); ?>
                                <span><?php echo esc_html__('Preview', 'textdomain'); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ($settings['display_mode'] === 'both' || $settings['display_mode'] === 'download') : ?>
                            <a href="<?php echo esc_url($doc['url']); ?>" 
                               class="pdf-action download-action" 
                               download>
                                <?php \Elementor\Icons_Manager::render_icon($settings['download_icon'], ['aria-hidden' => 'true']); ?>
                                <span><?php echo esc_html__('Download', 'textdomain'); ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    private function get_collection_id($post_id) {
        if (!function_exists('get_collection_id')) {
            return false;
        }
        return get_collection_id($post_id);
    }

    public function get_script_depends() {
        return [];
    }

    public function get_style_depends() {
        return [];
    }
}


// Add custom styles to head
function add_pdf_handler_styles() {
    ?>
    <style>
        /* Custom Animations */
        @keyframes buttonPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .pdf-action:hover {
            animation: buttonPulse 0.3s ease-in-out;
        }

        /* Additional Custom Styles */
        .pdf-type {
            font-size: 12px;
            color: #666;
            margin-left: 8px;
            padding: 2px 6px;
            border-radius: 3px;
            background: #f0f0f0;
        }
        
        .pdf-handler-wrapper {
            margin: 20px 0;
        }
        
        .pdf-handler-item {
            transition: transform 0.3s ease;
        }
        
        .pdf-handler-item:hover {
            transform: translateY(-2px);
        }

        /* Responsive Styles */
        @media (max-width: 767px) {
            .pdf-handler-item {
                width: 100%;
            }
            
            .pdf-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .pdf-action {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'add_pdf_handler_styles');
?>