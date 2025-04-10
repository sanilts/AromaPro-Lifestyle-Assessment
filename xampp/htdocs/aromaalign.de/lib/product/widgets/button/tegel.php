<?php
class Tegel_Cart_Button_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'tegel_cart_button';
    }

    public function get_title() {
        return 'Add Tegel to Cart Button';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['tm-widgets'];
    }

    protected function register_controls() {
        // Content Section - Button Text & Icon
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Content',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // In Stock Settings
        $this->add_control(
            'in_stock_heading',
            [
                'label' => 'In Stock Settings',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'in_stock_text',
            [
                'label' => 'Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Add to Cart',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'in_stock_icon',
            [
                'label' => 'Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-shopping-cart',
                    'library' => 'fa-solid',
                ],
            ]
        );

        // Available Soon Settings
        $this->add_control(
            'available_soon_heading',
            [
                'label' => 'Available Soon Settings',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'available_soon_text',
            [
                'label' => 'Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Pre-order (Available in 2 Weeks)',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'available_soon_icon',
            [
                'label' => 'Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-clock',
                    'library' => 'fa-solid',
                ],
            ]
        );

        // Out of Stock Settings
        $this->add_control(
            'out_of_stock_heading',
            [
                'label' => 'Out of Stock Settings',
                'type' => \Elementor\Controls_Manager::HEADING,
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'out_of_stock_text',
            [
                'label' => 'Text',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'Out of Stock',
                'dynamic' => [
                    'active' => true,
                ],
            ]
        );

        $this->add_control(
            'out_of_stock_icon',
            [
                'label' => 'Icon',
                'type' => \Elementor\Controls_Manager::ICONS,
                'default' => [
                    'value' => 'fas fa-times',
                    'library' => 'fa-solid',
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
                'separator' => 'before',
            ]
        );

        $this->add_control(
            'icon_spacing',
            [
                'label' => 'Icon Spacing',
                'type' => \Elementor\Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'max' => 50,
                    ],
                ],
                'selectors' => [
                    '{{WRAPPER}} .custom-cart-btn .button-icon-before' => 'margin-right: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .custom-cart-btn .button-icon-after' => 'margin-left: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
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
                    '{{WRAPPER}} .custom-cart-btn i' => 'font-size: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} .custom-cart-btn svg' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'size',
            [
                'label' => 'Size',
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'sm',
                'options' => [
                    'xs' => 'Extra Small',
                    'sm' => 'Small',
                    'md' => 'Medium',
                    'lg' => 'Large',
                    'xl' => 'Extra Large',
                ],
            ]
        );

        $this->end_controls_section();

        // Button Style Tab
        $this->start_controls_section(
            'section_style',
            [
                'label' => 'Button Styles',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .custom-cart-btn',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Text_Shadow::get_type(),
            [
                'name' => 'text_shadow',
                'selector' => '{{WRAPPER}} .custom-cart-btn',
            ]
        );

        $this->start_controls_tabs('style_tabs');

        // In Stock Style Tab
        $this->start_controls_tab(
            'style_in_stock',
            [
                'label' => 'In Stock',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'in_stock_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-in-stock',
            ]
        );

        $this->add_control(
            'in_stock_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-in-stock' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-in-stock svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'in_stock_border',
                'selector' => '{{WRAPPER}} .cart-btn-in-stock',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'in_stock_box_shadow',
                'selector' => '{{WRAPPER}} .cart-btn-in-stock',
            ]
        );

        $this->end_controls_tab();

        // Available Soon Style Tab
        $this->start_controls_tab(
            'style_available_soon',
            [
                'label' => 'Available Soon',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'available_soon_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-available-soon',
            ]
        );

        $this->add_control(
            'available_soon_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-available-soon' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-available-soon svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'available_soon_border',
                'selector' => '{{WRAPPER}} .cart-btn-available-soon',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'available_soon_box_shadow',
                'selector' => '{{WRAPPER}} .cart-btn-available-soon',
            ]
        );

        $this->end_controls_tab();

        // Out of Stock Style Tab
        $this->start_controls_tab(
            'style_out_of_stock',
            [
                'label' => 'Out of Stock',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'out_of_stock_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-out-of-stock',
            ]
        );

        $this->add_control(
            'out_of_stock_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-out-of-stock' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-out-of-stock svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Border::get_type(),
            [
                'name' => 'out_of_stock_border',
                'selector' => '{{WRAPPER}} .cart-btn-out-of-stock',
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Box_Shadow::get_type(),
            [
                'name' => 'out_of_stock_box_shadow',
                'selector' => '{{WRAPPER}} .cart-btn-out-of-stock',
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'border_radius',
            [
                'label' => 'Border Radius',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .custom-cart-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
                'separator' => 'before',
            ]
        );

        $this->add_responsive_control(
            'padding',
            [
                'label' => 'Padding',
                'type' => \Elementor\Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors' => [
                    '{{WRAPPER}} .custom-cart-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Hover Effects Section
        $this->start_controls_section(
            'section_hover',
            [
                'label' => 'Hover Effects',
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'hover_animation',
            [
                'label' => 'Hover Animation',
                'type' => \Elementor\Controls_Manager::HOVER_ANIMATION,
            ]
        );

        $this->add_control(
            'transition_duration',
            [
                'label' => 'Transition Duration',
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
                    '{{WRAPPER}} .custom-cart-btn' => 'transition-duration: {{SIZE}}s',
                ],
            ]
        );

        $this->start_controls_tabs('hover_tabs');

        // In Stock Hover
        $this->start_controls_tab(
            'hover_in_stock',
            [
                'label' => 'In Stock',
            ]
        );

        $this->add_control(
            'in_stock_hover_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-in-stock:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-in-stock:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'in_stock_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-in-stock:hover',
            ]
        );

        $this->add_control(
            'in_stock_hover_border_color',
            [
                'label' => 'Border Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-in-stock:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'in_stock_border_border!' => '',
                ],
            ]
        );

        $this->end_controls_tab();

        // Available Soon Hover
        $this->start_controls_tab(
            'hover_available_soon',
            [
                'label' => 'Available Soon',
            ]
        );

        $this->add_control(
            'available_soon_hover_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-available-soon:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-available-soon:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'available_soon_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-available-soon:hover',
            ]
        );

        $this->add_control(
            'available_soon_hover_border_color',
            [
                'label' => 'Border Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-available-soon:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'available_soon_border_border!' => '',
                ],
            ]
        );

        $this->end_controls_tab();

        // Out of Stock Hover
        $this->start_controls_tab(
            'hover_out_of_stock',
            [
                'label' => 'Out of Stock',
            ]
        );

        $this->add_control(
            'out_of_stock_hover_color',
            [
                'label' => 'Text Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-out-of-stock:hover' => 'color: {{VALUE}};',
                    '{{WRAPPER}} .cart-btn-out-of-stock:hover svg' => 'fill: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Background::get_type(),
            [
                'name' => 'out_of_stock_hover_background',
                'types' => ['classic', 'gradient'],
                'selector' => '{{WRAPPER}} .cart-btn-out-of-stock:hover',
            ]
        );

        $this->add_control(
            'out_of_stock_hover_border_color',
            [
                'label' => 'Border Color',
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .cart-btn-out-of-stock:hover' => 'border-color: {{VALUE}};',
                ],
                'condition' => [
                    'out_of_stock_border_border!' => '',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->end_controls_section();
    }

    private function get_product_stock_status() {
        global $product;
        
        if (!is_object($product)) {
            $product = wc_get_product(get_the_ID());
        }
        
        if (!$product) {
            return 'out_of_stock';
        }

        if ($product->is_on_backorder()) {
            return 'available_soon';
        }

        $stock_status = $product->get_stock_status();
        $manage_stock = $product->get_manage_stock();
        $stock_quantity = $product->get_stock_quantity();

        if ($manage_stock) {
            if ($stock_quantity > 0) {
                return 'in_stock';
            } elseif ($product->get_backorders_allowed()) {
                return 'available_soon';
            } else {
                return 'out_of_stock';
            }
        } else {
            switch ($stock_status) {
                case 'instock':
                    return 'in_stock';
                case 'onbackorder':
                    return 'available_soon';
                case 'outofstock':
                default:
                    return 'out_of_stock';
            }
        }
    }

    protected function render() {
        global $post;
        $settings = $this->get_settings_for_display();
        $stock_status = $this->get_product_stock_status();
        
        // Get the appropriate settings based on stock status
        switch($stock_status) {
            case 'in_stock':
                $button_class = 'cart-btn-in-stock';
                $button_text = $settings['in_stock_text'];
                $icon = $settings['in_stock_icon'];
                $disabled = false;
                break;
            case 'available_soon':
                $button_class = 'cart-btn-available-soon';
                $button_text = $settings['available_soon_text'];
                $icon = $settings['available_soon_icon'];
                $disabled = false;
                break;
            case 'out_of_stock':
                $button_class = 'cart-btn-out-of-stock';
                $button_text = $settings['out_of_stock_text'];
                $icon = $settings['out_of_stock_icon'];
                $disabled = true;
                break;
        }

        // Button wrapper classes
        $this->add_render_attribute([
            'wrapper' => [
                'class' => [
                    'elementor-button-wrapper',
                ],
            ],
            'button' => [
                'class' => [
                    'elementor-button',
                    'custom-cart-btn',
                    $button_class,
                    'elementor-size-' . $settings['size'] ?? 'sm',
                ],
                'role' => 'button',
            ],
        ]);

        if (!empty($settings['hover_animation'])) {
            $this->add_render_attribute('button', 'class', 'elementor-animation-' . $settings['hover_animation']);
        }

        if ($disabled) {
            $this->add_render_attribute('button', 'disabled', 'disabled');
        }

        // Icon Container
        $this->add_render_attribute('icon-align', 'class', [
            'elementor-button-icon',
            'elementor-align-icon-' . $settings['icon_position'],
        ]);

        // Content Container
        $this->add_render_attribute('content-wrapper', 'class', 'elementor-button-content-wrapper');
        $this->add_render_attribute('text', 'class', 'elementor-button-text');
        ?>
        <div <?php echo $this->get_render_attribute_string('wrapper'); ?>>
            <a href="?add-to-cart=<?php echo $post->ID; ?>"<?php echo $this->get_render_attribute_string('button'); ?>>
                <span <?php echo $this->get_render_attribute_string('content-wrapper'); ?>>
                    <?php if (!empty($icon['value'])) : ?>
                        <span <?php echo $this->get_render_attribute_string('icon-align'); ?>>
                            <?php \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']); ?>
                        </span>
                    <?php endif; ?>
                    <span <?php echo $this->get_render_attribute_string('text'); ?>><?php echo $button_text; ?></span>
                </span>
            </a>
        </div>
        <?php
    }
}