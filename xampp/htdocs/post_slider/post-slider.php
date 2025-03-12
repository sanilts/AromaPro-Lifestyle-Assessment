<?php
/**
 * Plugin Name: Custom Elementor Widgets
 * Description: Custom Elementor widgets including Post Type Slider with hover effects.
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: custom-elementor-widgets
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Plugin Class
 */
final class Custom_Elementor_Widgets {

    /**
     * Plugin Version
     */
    const VERSION = '1.0.0';

    /**
     * Minimum Elementor Version
     */
    const MINIMUM_ELEMENTOR_VERSION = '3.5.0';

    /**
     * Minimum PHP Version
     */
    const MINIMUM_PHP_VERSION = '7.3';

    /**
     * Instance
     */
    private static $_instance = null;

    /**
     * Instance
     *
     * Ensures only one instance of the class is loaded or can be loaded.
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        // Load plugin textdomain
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if Elementor installed and activated
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_main_plugin']);
            return;
        }

        // Check for required Elementor version
        if (!version_compare(ELEMENTOR_VERSION, self::MINIMUM_ELEMENTOR_VERSION, '>=')) {
            add_action('admin_notices', [$this, 'admin_notice_minimum_elementor_version']);
            return;
        }

        // Check for required PHP version
        if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
            add_action('admin_notice_minimum_php_version');
            return;
        }

        // Add Plugin actions
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
        
        // Legacy support for Elementor < 3.5.0
        add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets_legacy']);
        
        // Register category
        add_action('elementor/elements/categories_registered', [$this, 'add_elementor_widget_categories']);

        // Register Widget Styles
        add_action('elementor/frontend/after_enqueue_styles', [$this, 'widget_styles']);

        // Register Widget Scripts
        add_action('elementor/frontend/after_register_scripts', [$this, 'widget_scripts']);
    }

    /**
     * Admin notice
     * Warning when the site doesn't have Elementor installed or activated.
     */
    public function admin_notice_missing_main_plugin() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'custom-elementor-widgets'),
            '<strong>' . esc_html__('Custom Elementor Widgets', 'custom-elementor-widgets') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'custom-elementor-widgets') . '</strong>'
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     * Warning when the site doesn't have a minimum required Elementor version.
     */
    public function admin_notice_minimum_elementor_version() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor 3: Required Elementor version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'custom-elementor-widgets'),
            '<strong>' . esc_html__('Custom Elementor Widgets', 'custom-elementor-widgets') . '</strong>',
            '<strong>' . esc_html__('Elementor', 'custom-elementor-widgets') . '</strong>',
            self::MINIMUM_ELEMENTOR_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Admin notice
     * Warning when the site doesn't have a minimum required PHP version.
     */
    public function admin_notice_minimum_php_version() {
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }

        $message = sprintf(
            /* translators: 1: Plugin name 2: PHP 3: Required PHP version */
            esc_html__('"%1$s" requires "%2$s" version %3$s or greater.', 'custom-elementor-widgets'),
            '<strong>' . esc_html__('Custom Elementor Widgets', 'custom-elementor-widgets') . '</strong>',
            '<strong>' . esc_html__('PHP', 'custom-elementor-widgets') . '</strong>',
            self::MINIMUM_PHP_VERSION
        );

        printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
    }

    /**
     * Add custom category for our widgets
     */
    public function add_elementor_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'custom-elementor-widgets',
            [
                'title' => esc_html__('Custom Widgets', 'custom-elementor-widgets'),
                'icon' => 'fa fa-plug',
            ]
        );
    }

    /**
     * Register widgets
     */
    public function register_widgets($widgets_manager) {
        // Check if the widget file exists
        $widget_file = plugin_dir_path(__FILE__) . 'widgets/class-post-type-slider-widget.php';
        
        if (file_exists($widget_file)) {
            // Include Widget file
            require_once($widget_file);
            
            // Register the widget
            $widgets_manager->register(new Post_Type_Slider_Widget());
        } else {
            // Add an admin notice if the file is missing
            add_action('admin_notices', function() use ($widget_file) {
                $message = sprintf(
                    /* translators: %s: Widget file path */
                    esc_html__('Widget file not found: %s. Please check if the file exists in the correct location.', 'custom-elementor-widgets'),
                    '<code>' . esc_html($widget_file) . '</code>'
                );
                printf('<div class="notice notice-error is-dismissible"><p>%1$s</p></div>', $message);
            });
        }
    }
    
    /**
     * Legacy support for Elementor < 3.5.0
     */
    public function register_widgets_legacy() {
        // Check if the widget file exists
        $widget_file = plugin_dir_path(__FILE__) . 'widgets/class-post-type-slider-widget.php';
        
        if (file_exists($widget_file)) {
            // Include Widget file
            require_once($widget_file);
            
            // Register the widget
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Post_Type_Slider_Widget());
        }
    }

    /**
     * Register Widget Styles
     */
    public function widget_styles() {
        // Slick Slider CSS
        wp_register_style('slick', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css', [], '1.8.1');
        wp_register_style('slick-theme', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css', [], '1.8.1');
        
        // Load Font Awesome for arrows if needed
        wp_register_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', [], '5.15.4');
        
        // Plugin custom CSS - check if the file exists
        $css_file = plugin_dir_path(__FILE__) . 'css/post-type-slider.css';
        
        if (file_exists($css_file)) {
            wp_register_style(
                'custom-elementor-widgets',
                plugins_url('css/post-type-slider.css', __FILE__),
                ['slick', 'slick-theme', 'font-awesome'],
                self::VERSION
            );
        } else {
            // If the CSS file doesn't exist, add inline CSS for the basic functionality
            wp_register_style(
                'custom-elementor-widgets',
                false
            );
            
            wp_add_inline_style('custom-elementor-widgets', '
                /* Basic slider styling */
                .pts-post-type-slider {
                    visibility: visible !important;
                    opacity: 1 !important;
                }
                
                .pts-slider-image-wrapper {
                    position: relative;
                    overflow: hidden;
                    border-radius: 5px;
                    height: 400px;
                }
                
                .pts-slider-image {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                }
                
                .pts-slider-image img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }
                
                .pts-slider-image.grayscale {
                    filter: grayscale(100%);
                    opacity: 1;
                }
                
                .pts-slider-image.color {
                    opacity: 0;
                }
                
                .pts-slider-item:hover .pts-slider-image.grayscale {
                    opacity: 0;
                }
                
                .pts-slider-item:hover .pts-slider-image.color {
                    opacity: 1;
                }
                
                .pts-slider-title-wrapper {
                    position: absolute;
                    bottom: 0;
                    left: 0;
                    width: 100%;
                    background-color: rgba(0,0,0,0.7);
                    transform: translateY(100%);
                    transition: transform 0.4s ease-in-out;
                }
                
                .pts-slider-item:hover .pts-slider-title-wrapper {
                    transform: translateY(0);
                }
                
                /* Arrow styling */
                .pts-post-type-slider .slick-prev,
                .pts-post-type-slider .slick-next {
                    width: 40px !important;
                    height: 40px !important;
                    background-color: rgba(0, 0, 0, 0.5) !important;
                    border-radius: 50% !important;
                    z-index: 1000 !important;
                    display: block !important;
                    visibility: visible !important;
                }
            ');
        }

        wp_enqueue_style('slick');
        wp_enqueue_style('slick-theme');
        wp_enqueue_style('font-awesome');
        wp_enqueue_style('custom-elementor-widgets');
    }

    /**
     * Register Widget Scripts
     */
    public function widget_scripts() {
        // Slick Slider JS
        wp_register_script(
            'slick',
            'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js',
            ['jquery'],
            '1.8.1',
            true
        );
        
        // Make sure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Ensure Slick is properly loaded
        wp_enqueue_script('slick');
        
        // Check if the JS file exists
        $js_file = plugin_dir_path(__FILE__) . 'js/post-type-slider.js';
        
        if (file_exists($js_file)) {
            wp_register_script(
                'custom-elementor-widgets',
                plugins_url('js/post-type-slider.js', __FILE__),
                ['jquery', 'slick'],
                self::VERSION,
                true
            );
        } else {
            // If the JS file doesn't exist, add inline JS for the basic functionality
            wp_register_script(
                'custom-elementor-widgets',
                '',
                ['jquery', 'slick'],
                self::VERSION,
                true
            );
            
            wp_add_inline_script('custom-elementor-widgets', '
                jQuery(document).ready(function($) {
                    setTimeout(function() {
                        $(".pts-post-type-slider").each(function() {
                            var $slider = $(this);
                            var settings = $slider.data("settings") || {};
                            
                            if ($slider.hasClass("slick-initialized")) {
                                $slider.slick("unslick");
                            }
                            
                            $slider.slick(settings);
                        });
                    }, 1000);
                });
            ');
        }

        wp_enqueue_script('custom-elementor-widgets');
        
        // Add debug information to help troubleshoot
        if (current_user_can('administrator')) {
            wp_add_inline_script('custom-elementor-widgets', '
                console.log("Custom Elementor Widgets scripts loaded");
                console.log("jQuery version:", jQuery.fn.jquery);
                console.log("Slick loaded:", typeof jQuery.fn.slick !== "undefined");
            ');
        }
    }
}

// Initialize the plugin
Custom_Elementor_Widgets::instance();