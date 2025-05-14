<?php
/**
 * Enhanced Logging Integration - Fixed Version
 * 
 * This file integrates all enhanced logging functionality into the plugin
 * with corrected file paths and class names
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Enhanced_Logging {
    /**
     * Initialize the enhanced logging
     */
    public static function init() {
        // Check if the plugin is active
        if (!function_exists('cgptfc_main')) {
            return;
        }
        
        // Include required files
        self::include_files();
        
        // Hook into the plugins_loaded action to initialize after the main plugin
        add_action('plugins_loaded', array(__CLASS__, 'setup_hooks'), 20);
    }
    
    /**
     * Include required files
     */
    public static function include_files() {
        // Path to the enhanced logging directory
        $base_path = plugin_dir_path(__FILE__);
        
        // Include the enhanced response logger class - FIXED CLASS NAME
        if (!class_exists('CGPTFC_Enhanced_Response_Logger')) {
            require_once $base_path . 'class-cgptfc-enhanced-response-logger.php';
        }
        
        // Include the API integration files - make sure these files exist
        if (file_exists($base_path . 'api-integration.php')) {
            require_once $base_path . 'api-integration.php';
        }
        
        // Include API class modifications
        if (file_exists($base_path . 'chatgpt-api-modifications.php')) {
            require_once $base_path . 'chatgpt-api-modifications.php';
        }
        
        if (file_exists($base_path . 'gemini-api-modifications.php')) {
            require_once $base_path . 'gemini-api-modifications.php';
        }
    }
    
    /**
     * Set up hooks and filters
     */
    public static function setup_hooks() {
        // Only proceed if CGPTFC_Enhanced_Response_Logger exists
        if (!class_exists('CGPTFC_Enhanced_Response_Logger')) {
            error_log('CGPTFC: Enhanced Response Logger class not found');
            return;
        }
        
        // Replace the standard response logger with our enhanced one
        self::replace_response_logger();
        
        // Add hooks for API class modifications
        if (function_exists('cgptfc_enhance_openai_api') && function_exists('cgptfc_enhance_gemini_api')) {
            self::setup_api_hooks();
        }
        
        // Add admin UI modifications
        add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'));
    }
    
    /**
     * Replace the standard response logger with our enhanced logger
     */
    public static function replace_response_logger() {
        $main = cgptfc_main();
        
        // Only proceed if the main plugin is initialized
        if (!$main) {
            return;
        }
        
        // Replace the response logger with our enhanced version
        if (isset($main->response_logger) && class_exists('CGPTFC_Enhanced_Response_Logger')) {
            $main->response_logger = new CGPTFC_Enhanced_Response_Logger();
        } else {
            error_log('CGPTFC: Failed to replace response logger. Class not found or main object not ready.');
        }
    }
    
    /**
     * Set up API hooks and filters
     */
    public static function setup_api_hooks() {
        // Hook into the process_form_with_prompt method of the OpenAI API class
        add_action('init', function() {
            if (class_exists('CGPTFC_API')) {
                // Add our wrapper around the process_form_with_prompt method
                add_filter('cgptfc_process_form_with_prompt', 'cgptfc_enhanced_process_form_with_prompt', 10, 2);
            }
            
            if (class_exists('CGPTFC_Gemini_API')) {
                // Add our wrapper around the process_form_with_prompt method for Gemini
                add_filter('cgptfc_process_form_with_prompt_gemini', 'cgptfc_enhanced_gemini_process_form_with_prompt', 10, 2);
            }
        });
        
        // Initialize our enhanced API integrations
        if (function_exists('cgptfc_enhance_openai_api')) {
            cgptfc_enhance_openai_api();
        }
        
        if (function_exists('cgptfc_enhance_gemini_api')) {
            cgptfc_enhance_gemini_api();
        }
    }
    
    /**
     * Enqueue admin scripts and styles for the enhanced logging UI
     */
    public static function admin_enqueue_scripts($hook) {
        // Only load on our log pages
        if (strpos($hook, 'cgptfc-response-logs') === false) {
            return;
        }
        
        // Make sure the CSS and JS files exist before trying to enqueue them
        $css_path = plugin_dir_path(__FILE__) . 'css/enhanced-logs.css';
        $js_path = plugin_dir_path(__FILE__) . 'js/enhanced-logs.js';
        
        // Enqueue CSS for log styling if it exists
        if (file_exists($css_path)) {
            wp_enqueue_style(
                'cgptfc-enhanced-logs',
                plugins_url('css/enhanced-logs.css', __FILE__),
                array(),
                CGPTFC_VERSION
            );
        }
        
        // Enqueue JavaScript for log interactions if it exists
        if (file_exists($js_path)) {
            wp_enqueue_script(
                'cgptfc-enhanced-logs',
                plugins_url('js/enhanced-logs.js', __FILE__),
                array('jquery'),
                CGPTFC_VERSION,
                true
            );
        }
    }
}

// Save original Response Logger
if (class_exists('CGPTFC_Response_Logger')) {
    // Rename the original response logger class to avoid conflicts, but only if it hasn't been done
    if (!class_exists('CGPTFC_Original_Response_Logger')) {
        class_alias('CGPTFC_Response_Logger', 'CGPTFC_Original_Response_Logger');
    }
}

// Initialize our enhanced logging with additional error handling
try {
    CGPTFC_Enhanced_Logging::init();
} catch (Exception $e) {
    error_log('CGPTFC Enhanced Logging Error: ' . $e->getMessage());
}