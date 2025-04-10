<?php
class MonsterMap_Add_To_Cart_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'monster_map__add_to_cart';
    }

    public function get_title() {
        return 'Monster Map Add To Cart';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    protected function register_controls() {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Button Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'button_text',
            [
                'label' => 'Button Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Add to Cart',
            ]
        );

        // Icon Controls
        $this->add_control(
            'show_icon',
            [
                'label' => 'Show Icon',
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => 'Yes',
                'label_off' => 'No',
                'return_value' => 'yes',
                'default' => 'yes',
            ]
        );

        $this->add_control(
            'selected_icon',
            [
                'label' => 'Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'fa4compatibility' => 'icon',
                'default' => [
                    'value' => 'fas fa-shopping-cart',
                    'library' => 'fa-solid',
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_position',
            [
                'label' => 'Icon Position',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'before',
                'options' => [
                    'before' => 'Before',
                    'after' => 'After',
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_spacing',
            [
                'label' => 'Icon Spacing',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 0,
                        'max' => 50,
                    ],
                ],
                'default' => [
                    'size' => 8,
                ],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button .icon-before' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .custom-add-to-cart-button .icon-after' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => 'Button Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        // Typography
        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'button_typography',
                'selector' => '{{WRAPPER}} .custom-add-to-cart-button',
            ]
        );

        // Normal state controls
        $this->start_controls_tabs('button_style_tabs');

        $this->start_controls_tab(
            'button_style_normal',
            [
                'label' => 'Normal',
            ]
        );

        $this->add_control(
            'button_text_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_background_color',
            [
                'label' => 'Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#0073aa',
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        // Hover state controls
        $this->start_controls_tab(
            'button_style_hover',
            [
                'label' => 'Hover',
            ]
        );

        $this->add_control(
            'button_hover_text_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'button_hover_background_color',
            [
                'label' => 'Background Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button:hover' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();
        $this->end_controls_tabs();

        // Border
        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'button_border',
                'selector' => '{{WRAPPER}} .custom-add-to-cart-button',
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'button_border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%'],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Padding
        $this->add_responsive_control(
            'button_padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Margin
        $this->add_responsive_control(
            'button_margin',
            [
                'label' => 'Margin',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Box Shadow
        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'button_box_shadow',
                'selector' => '{{WRAPPER}} .custom-add-to-cart-button',
            ]
        );

        // Button Width
        $this->add_responsive_control(
            'button_width',
            [
                'label' => 'Button Width',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'auto',
                'options' => [
                    'auto' => 'Auto',
                    'full' => 'Full Width',
                    'custom' => 'Custom',
                ],
            ]
        );

        $this->add_responsive_control(
            'button_custom_width',
            [
                'label' => 'Custom Width',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['px', '%'],
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 500,
                    ],
                    '%' => [
                        'min' => 0,
                        'max' => 100,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button' => 'width: {{SIZE}}{{UNIT}};',
                ],
                'condition' => [
                    'button_width' => 'custom',
                ],
            ]
        );

        $this->end_controls_section();

        // Icon Style Section
        $this->start_controls_section(
            'icon_style_section',
            [
                'label' => 'Icon Style',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_icon' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'icon_color',
            [
                'label' => 'Icon Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .custom-add-to-cart-button svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_hover_color',
            [
                'label' => 'Icon Hover Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button:hover i' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .custom-add-to-cart-button:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'icon_size',
            [
                'label' => 'Icon Size',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 6,
                        'max' => 300,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .custom-add-to-cart-button i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .custom-add-to-cart-button svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    private function get_collection_id($post_id) {
        if (!function_exists('get_collection_id')) {
            return false;
        }
        return get_collection_id($post_id);
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
        
        
        $monstermap_id = get_post_meta($post->ID, 'monstermap', true);
        
        if (!$monstermap_id) {
            return;
        }

        $button_text = $settings['button_text'];
        $button_url = add_query_arg(
            array(
                'add-to-cart' => $monstermap_id,
                'quantity' => 1,
            ),
            wc_get_cart_url()
        );

        // Prepare icon HTML
        $icon_html = '';
        if ($settings['show_icon'] === 'yes' && !empty($settings['selected_icon']['value'])) {
            $icon_position_class = 'icon-' . $settings['icon_position'];
            ob_start();
            \Elementor\Icons_Manager::render_icon($settings['selected_icon'], ['aria-hidden' => 'true', 'class' => $icon_position_class]);
            $icon_html = ob_get_clean();
        }
        ?>
        <div class="custom-add-to-cart-container">
            <a href="<?php echo esc_url($button_url); ?>" 
               class="custom-add-to-cart-button">
                <?php if ($settings['show_icon'] === 'yes' && $settings['icon_position'] === 'before') : ?>
                    <?php echo $icon_html; ?>
                <?php endif; ?>
                
                <span class="button-text"><?php echo esc_html($button_text); ?></span>
                
                <?php if ($settings['show_icon'] === 'yes' && $settings['icon_position'] === 'after') : ?>
                    <?php echo $icon_html; ?>
                <?php endif; ?>
            </a>
        </div>
        <style>
            .custom-add-to-cart-button {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                padding: 9px 20px;
                color: #ffffff;
                text-decoration: none;
                border-radius: 4px;
                transition: all 0.3s ease;
            }
            .custom-add-to-cart-button:hover {
                opacity: 0.9;
            }
            .custom-add-to-cart-button i,
            .custom-add-to-cart-button svg {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                transition: all 0.3s ease;
            }
            .custom-add-to-cart-button .button-text {
                display: inline-block;
            }
        </style>
        <?php
    }
}