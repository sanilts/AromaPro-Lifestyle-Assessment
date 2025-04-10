<?php

class Custom_Woo_Add_To_Cart extends \Elementor\Widget_Base {

    public function get_name() {
        return 'custom_woo_add_to_cart';
    }

    public function get_title() {
        return 'Custom Add to Cart Button';
    }

    public function get_icon() {
        return 'eicon-cart';
    }

    public function get_categories() {
        return ['woocommerce-elements'];
    }

    protected function register_controls() {
        // Product Selection Section
        $this->start_controls_section(
                'product_section',
                [
                    'label' => 'Product Selection',
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'product_id',
                [
                    'label' => 'Product ID',
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'dynamic' => [
                        'active' => true,
                        'categories' => [
                            \Elementor\Modules\DynamicTags\Module::POST_META_CATEGORY,
                            \Elementor\Modules\DynamicTags\Module::NUMBER_CATEGORY,
                        ],
                    ],
                ]
        );

        $this->end_controls_section();

        // Message Position Section
        $this->start_controls_section(
                'message_position_section',
                [
                    'label' => 'Message Position',
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'message_position',
                [
                    'label' => 'Message Position',
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'default' => 'top',
                    'options' => [
                        'top' => 'Top',
                        'bottom' => 'Bottom',
                        'left' => 'Left',
                        'right' => 'Right',
                    ],
                    'prefix_class' => 'message-position-',
                ]
        );

        $this->end_controls_section();
        // Button Content Section
        $this->start_controls_section(
                'button_content_section',
                [
                    'label' => 'Button Content',
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'button_text',
                [
                    'label' => 'Button Text',
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'default' => 'Add to cart',
                ]
        );

        $this->add_control(
                'selected_icon',
                [
                    'label' => 'Icon',
                    'type' => \Elementor\Controls_Manager::ICONS,
                    'default' => [
                        'value' => 'fas fa-shopping-cart',
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
                        '{{WRAPPER}} .button i' => 'font-size: {{SIZE}}{{UNIT}};',
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
                    'selectors' => [
                        '{{WRAPPER}} .button.icon-before i' => 'margin-right: {{SIZE}}{{UNIT}};',
                        '{{WRAPPER}} .button.icon-after i' => 'margin-left: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Content Section
        $this->start_controls_section(
                'stock_messages_section',
                [
                    'label' => 'Stock Messages',
                    'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
                ]
        );

        $this->add_control(
                'in_stock_message',
                [
                    'label' => 'In Stock Message',
                    'type' => \Elementor\Controls_Manager::WYSIWYG,
                    'default' => 'Vandaag besteld, morgen in uw brievenbus.',
                ]
        );

        $this->add_control(
                'backorder_message',
                [
                    'label' => 'Back Order Message',
                    'type' => \Elementor\Controls_Manager::WYSIWYG,
                    'default' => 'Bestelt u vandaag? Dan wordt dit artikel binnen ongeveer twee weken door de leverancier geleverd.',
                ]
        );

        $this->add_control(
                'out_of_stock_message',
                [
                    'label' => 'Out of Stock Message',
                    'type' => \Elementor\Controls_Manager::WYSIWYG,
                    'default' => 'Helaas is dit artikel niet meer te bestellen',
                ]
        );

        $this->end_controls_section();

        // Message Wrapper Style Section
        $this->start_controls_section(
                'message_wrapper_style',
                [
                    'label' => 'Message Box Style',
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_responsive_control(
                'button_wrapper_width',
                [
                    'label' => 'Button Width',
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
                        '{{WRAPPER}} .button-wrapper' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );
        $this->add_responsive_control(
                'message_padding',
                [
                    'label' => 'Padding',
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .stock-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        // Message Position styles
        $this->add_control(
                'layout_heading',
                [
                    'label' => 'Layout',
                    'type' => \Elementor\Controls_Manager::HEADING,
                    'separator' => 'before',
                ]
        );

        $this->add_responsive_control(
                'wrapper_width',
                [
                    'label' => 'Width',
                    'type' => \Elementor\Controls_Manager::SLIDER,
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
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .custom-add-to-cart-wrapper' => 'width: {{SIZE}}{{UNIT}};',
                    ],
                ]
        );

        $this->add_control(
                'use_flex',
                [
                    'label' => 'Enable Flex Layout',
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'yes',
                    'return_value' => 'yes',
                    'selectors' => [
                        '{{WRAPPER}} .custom-add-to-cart-wrapper' => 'display: flex; gap: 10px;',
                    ],
                ]
        );

        $this->add_control(
                'flex_direction',
                [
                    'label' => 'Flex Direction',
                    'type' => \Elementor\Controls_Manager::HIDDEN,
                    'default' => 'column',
                    'selectors' => [
                        '{{WRAPPER}}.message-position-top .custom-add-to-cart-wrapper' => 'flex-direction: column; align-items: stretch;',
                        '{{WRAPPER}}.message-position-bottom .custom-add-to-cart-wrapper' => 'flex-direction: column-reverse; align-items: stretch;',
                        '{{WRAPPER}}.message-position-left .custom-add-to-cart-wrapper' => 'flex-direction: row; align-items: center;',
                        '{{WRAPPER}}.message-position-right .custom-add-to-cart-wrapper' => 'flex-direction: row-reverse; align-items: center;',
                    ],
                    'condition' => [
                        'use_flex' => 'yes',
                    ],
                ]
        );

        $this->add_responsive_control(
                'alignment',
                [
                    'label' => 'Alignment',
                    'type' => \Elementor\Controls_Manager::CHOOSE,
                    'options' => [
                        'flex-start' => [
                            'title' => 'Left',
                            'icon' => 'eicon-text-align-left',
                        ],
                        'center' => [
                            'title' => 'Center',
                            'icon' => 'eicon-text-align-center',
                        ],
                        'flex-end' => [
                            'title' => 'Right',
                            'icon' => 'eicon-text-align-right',
                        ],
                    ],
                    'selectors' => [
                        '{{WRAPPER}} .custom-add-to-cart-wrapper' => 'justify-content: {{VALUE}};',
                    ],
                    'condition' => [
                        'use_flex' => 'yes',
                    ],
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'message_border',
                    'selector' => '{{WRAPPER}} .stock-message',
                ]
        );

        $this->add_control(
                'message_border_radius',
                [
                    'label' => 'Border Radius',
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .stock-message' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ],
                ]
        );

        $this->end_controls_section();

        // Message Text Styles
        $this->start_controls_section(
                'message_text_style',
                [
                    'label' => 'Message Text Style',
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'message_typography',
                    'selector' => '{{WRAPPER}} .stock-message',
                ]
        );

        // In Stock Style
        $this->start_controls_tabs('message_styles_tabs');

        $this->start_controls_tab(
                'message_in_stock_style',
                [
                    'label' => 'In Stock',
                ]
        );

        $this->add_control(
                'in_stock_color',
                [
                    'label' => 'Text Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#4CAF50',
                    'selectors' => [
                        '{{WRAPPER}} .in-stock-message' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'in_stock_background',
                [
                    'label' => 'Background Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => 'rgba(76, 175, 80, 0.1)',
                    'selectors' => [
                        '{{WRAPPER}} .in-stock-message' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_tab();

        // Back Order Style
        $this->start_controls_tab(
                'message_backorder_style',
                [
                    'label' => 'Back Order',
                ]
        );

        $this->add_control(
                'backorder_color',
                [
                    'label' => 'Text Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#FFA500',
                    'selectors' => [
                        '{{WRAPPER}} .backorder-message' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'backorder_background',
                [
                    'label' => 'Background Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => 'rgba(255, 165, 0, 0.1)',
                    'selectors' => [
                        '{{WRAPPER}} .backorder-message' => 'background-color: {{VALUE}};',
                    ],
                ]
        );

        $this->end_controls_tab();

        // Out of Stock Style
        $this->start_controls_tab(
                'message_out_of_stock_style',
                [
                    'label' => 'Out of Stock',
                ]
        );

        $this->add_control(
                'out_of_stock_color',
                [
                    'label' => 'Text Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => '#FF0000',
                    'selectors' => [
                        '{{WRAPPER}} .out-of-stock-message' => 'color: {{VALUE}};',
                    ],
                ]
        );

        $this->add_control(
                'out_of_stock_background',
                [
                    'label' => 'Background Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'default' => 'rgba(255, 0, 0, 0.1)',
                    'selectors' => [
                        '{{WRAPPER}} .out-of-stock-message' => 'background-color: {{VALUE}};',
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
                    'label' => 'Button Styles',
                    'tab' => \Elementor\Controls_Manager::TAB_STYLE,
                ]
        );

        $this->add_group_control(
                \Elementor\Group_Control_Typography::get_type(),
                [
                    'name' => 'button_typography',
                    'selector' => '{{WRAPPER}} .button',
                ]
        );

        $this->start_controls_tabs('button_styles_tabs');

        $this->start_controls_tab(
                'button_normal_style',
                [
                    'label' => 'Normal',
                ]
        );

        $this->add_control(
                'button_text_color',
                [
                    'label' => 'Text Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .button' => 'color: {{VALUE}} !important;',
                    ],
                ]
        );

        $this->add_control(
                'button_background_color',
                [
                    'label' => 'Background Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .button' => 'background-color: {{VALUE}} !important;',
                    ],
                ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
                'button_hover_style',
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
                        '{{WRAPPER}} .button:hover' => 'color: {{VALUE}} !important;',
                    ],
                ]
        );

        $this->add_control(
                'button_hover_background_color',
                [
                    'label' => 'Background Color',
                    'type' => \Elementor\Controls_Manager::COLOR,
                    'selectors' => [
                        '{{WRAPPER}} .button:hover' => 'background-color: {{VALUE}} !important;',
                    ],
                ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_group_control(
                \Elementor\Group_Control_Border::get_type(),
                [
                    'name' => 'button_border',
                    'selector' => '{{WRAPPER}} .button',
                ]
        );

        $this->add_control(
                'button_border_radius',
                [
                    'label' => 'Border Radius',
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
        );

        $this->add_responsive_control(
                'button_padding',
                [
                    'label' => 'Padding',
                    'type' => \Elementor\Controls_Manager::DIMENSIONS,
                    'size_units' => ['px', 'em', '%'],
                    'selectors' => [
                        '{{WRAPPER}} .button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}} !important;',
                    ],
                ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Get product
        if (!empty($settings['product_id'])) {
            $product = wc_get_product($settings['product_id']);
        } else {
            global $product;
        }

        if (!$product) {
            return;
        }

        $stock_status = $product->get_stock_status();

        switch ($stock_status) {
            case 'instock':
                $message = $settings['in_stock_message'];
                $message_class = 'in-stock-message';
                break;
            case 'onbackorder':
                $message = $settings['backorder_message'];
                $message_class = 'backorder-message';
                break;
            case 'outofstock':
                $message = $settings['out_of_stock_message'];
                $message_class = 'out-of-stock-message';
                break;
        }
        ?>
        <div class="custom-add-to-cart-wrapper">
            <div class="stock-message <?php echo esc_attr($message_class); ?>">
            <?php echo wp_kses_post($message); ?>
            </div>
            <?php
            if ($stock_status !== 'outofstock') {
                $icon_position = $settings['icon_position'] === 'before' ? 'icon-before' : 'icon-after';
                $button_class = 'button product_type_' . $product->get_type() . ' button-wrapper add_to_cart_button ajax_add_to_cart ' . $icon_position;

                $icon_html = '';
                if (!empty($settings['selected_icon']['value'])) {
                    $this->add_render_attribute('i', 'class', $settings['selected_icon']['value']);
                    $this->add_render_attribute('i', 'aria-hidden', 'true');
                    $icon_html = '<i ' . $this->get_render_attribute_string('i') . '></i>';
                }

                $button_text = !empty($settings['button_text']) ? $settings['button_text'] : __('Add to cart', 'woocommerce');

                $button_html = sprintf(
                        '<a href="%s" data-quantity="1" class="%s " data-product_id="%s" rel="nofollow">%s%s</a>',
                        esc_url($product->add_to_cart_url()),
                        esc_attr($button_class),
                        esc_attr($product->get_id()),
                        $settings['icon_position'] === 'before' ? $icon_html . ' ' . $button_text : $button_text . ' ' . $icon_html,
                        ''
                );

                echo $button_html;
            }else{
                $icon_position = $settings['icon_position'] === 'before' ? 'icon-before' : 'icon-after';
                $button_class = 'button product_type_' . $product->get_type() . ' button-wrapper add_to_cart_button ajax_add_to_cart ' . $icon_position;

                $icon_html = '';
                if (!empty($settings['selected_icon']['value'])) {
                    $this->add_render_attribute('i', 'class', $settings['selected_icon']['value']);
                    $this->add_render_attribute('i', 'aria-hidden', 'true');
                    $icon_html = '<i ' . $this->get_render_attribute_string('i') . '></i>';
                }

                $button_text = !empty($settings['button_text']) ? $settings['button_text'] : __('Niet in staat om te bestellen', 'woocommerce');

                $button_html = sprintf(
                        '<a data-quantity="1" class="%s" data-product_id="%s" style="background-color:#999 !important;  pointer-events: none;" rel="nofollow">%s</a>',
                        esc_attr($button_class),
                        esc_attr($product->get_id()),
                        $settings['icon_position'] === 'before' ? $icon_html . ' ' . $button_text : $button_text . ' ' . $icon_html,
                        ''
                );
                echo $button_html;
            }
            ?>
        </div>
        <?php
    }
}
