<?php
/**
 * Plugin Name: ChatGPT Fluent Forms Connector
 * Plugin URI: https://aromapro.com/
 * Description: Connect Fluent Forms with ChatGPT to generate AI responses for form submissions
 * Version: 1.0.1
 * Author: Sanil T S
 * Author URI: https://www.fb.com/sanilts
 * License: GPL-2.0+
 * Text Domain: chatgpt-fluent-connector
 * Requires at least: 5.0
 * Requires PHP: 7.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CGPTFC_DIR', plugin_dir_path(__FILE__));
define('CGPTFC_URL', plugin_dir_url(__FILE__));
define('CGPTFC_VERSION', '1.0.0');

/**
 * Main plugin class
 */
class CGPTFC_Main {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Settings class instance
     */
    public $settings;
    
    /**
     * API class instance
     */
    public $api;
    
    /**
     * Prompt CPT class instance
     */
    public $prompt_cpt;
    
    /**
     * Fluent Forms integration class instance
     */
    public $fluent_integration;
    
    /**
     * Response logger class instance
     */
    public $response_logger;
    
    /**
     * Get the singleton instance
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Include required files - moved to init to avoid timing issues
        add_action('plugins_loaded', array($this, 'include_files'));
    }

    /**
     * Include the required files
     */
    public function include_files() {
        require_once __DIR__ . '/includes/class-chatgpt-settings.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-api.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-prompt-cpt.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-fluent-integration.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-response-logger.php';
        
        // Instantiate classes
        $this->settings = new CGPTFC_Settings();
        $this->api = new CGPTFC_API();
        $this->prompt_cpt = new CGPTFC_Prompt_CPT();
        $this->response_logger = new CGPTFC_Response_Logger();
        
        // Create the Fluent integration and ALSO explicitly register the hook outside the class
        $this->fluent_integration = new CGPTFC_Fluent_Integration();
        add_action('fluentform/submission_inserted', array($this->fluent_integration, 'handle_form_submission'), 20, 3);
        
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));
        
        // Load text domain
        add_action('init', array($this, 'load_textdomain'));
    }
    
    /**
     * Plugin activation
     */
    public function plugin_activation() {
        // Make sure the classes are loaded
        if (!isset($this->response_logger)) {
            $this->include_files();
        }
        
        // Create logs table
        if (isset($this->response_logger)) {
            $this->response_logger->create_logs_table();
        }
        
        // Set default options
        if (!get_option('cgptfc_api_endpoint')) {
            update_option('cgptfc_api_endpoint', 'https://api.openai.com/v1/chat/completions');
        }
        
        if (!get_option('cgptfc_model')) {
            update_option('cgptfc_model', 'gpt-3.5-turbo');
        }
        
        // Flush rewrite rules after creating custom post type
        flush_rewrite_rules();
    }
    
    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('chatgpt-fluent-connector', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
}

/**
 * Returns the main instance of the plugin
 */
function cgptfc_main() {
    return CGPTFC_Main::get_instance();
}

// Get the plugin running
add_action('plugins_loaded', 'cgptfc_main', 5);