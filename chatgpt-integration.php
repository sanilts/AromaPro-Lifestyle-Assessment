<?php
/**
 * Plugin Name: ChatGPT and Gemini Fluent Forms Connector
 * Plugin URI: https://aromapro.com/
 * Description: Connect Fluent Forms with ChatGPT or Google Gemini to generate AI responses for form submissions
 * Version: 1.1.0
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
define('CGPTFC_VERSION', '1.1.0');

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
     * OpenAI API class instance
     */
    public $api;

    /**
     * Gemini API class instance
     */
    public $gemini_api;

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
     * HTML Template uploader class instance
     */
    public $html_template_uploader;

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
        require_once __DIR__ . '/includes/class-gemini-api.php'; // New Gemini API class
        require_once __DIR__ . '/includes/class-chatgpt-custom-prompt-cpt.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-fluent-integration.php';
        require_once __DIR__ . '/includes/class-chatgpt-custom-response-logger.php';
        require_once __DIR__ . '/includes/class-chatgpt-html-template-uploader.php';
        require_once __DIR__ . '/includes/enhanced-logging/enhanced-logging.php';
        require_once __DIR__ . '/response-logger-integration.php';

        // Instantiate classes
        $this->settings = new CGPTFC_Settings();
        $this->api = new CGPTFC_API();
        $this->gemini_api = new CGPTFC_Gemini_API(); // Initialize Gemini API
        $this->prompt_cpt = new CGPTFC_Prompt_CPT();
        $this->response_logger = new CGPTFC_Response_Logger();
        $this->fluent_integration = new CGPTFC_Fluent_Integration();
        $this->html_template_uploader = new CGPTFC_HTML_Template_Uploader();

        // Register hooks
        add_action('fluentform/submission_inserted', array($this->fluent_integration, 'handle_form_submission'), 20, 3);

        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'plugin_activation'));

        // Load text domain
        add_action('init', array($this, 'load_textdomain'));

        // Add admin notice for first-time setup
        add_action('admin_notices', array($this, 'admin_setup_notice'));

        // Add CSS for admin
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'));
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

        // Set default options for OpenAI
        if (!get_option('cgptfc_api_endpoint')) {
            update_option('cgptfc_api_endpoint', 'https://api.openai.com/v1/chat/completions');
        }

        if (!get_option('cgptfc_model')) {
            update_option('cgptfc_model', 'gpt-3.5-turbo');
        }

        // Set default options for Gemini
        if (!get_option('cgptfc_gemini_api_endpoint')) {
            update_option('cgptfc_gemini_api_endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/');
        }

        if (!get_option('cgptfc_gemini_model')) {
            update_option('cgptfc_gemini_model', 'gemini-pro');
        }

        // Set default API provider
        if (!get_option('cgptfc_api_provider')) {
            update_option('cgptfc_api_provider', 'openai');
        }

        // In the plugin_activation() method of your main class
        if (isset($this->response_logger)) {
            // Force table version check and update
            $this->response_logger->check_table_version();
        }

        // Flush rewrite rules after creating custom post type
        flush_rewrite_rules();
    }

    /**
     * Display admin notice for first-time setup
     */
    public function admin_setup_notice() {
        $screen = get_current_screen();

        // Only show on the plugins page or our settings pages
        if (!in_array($screen->id, array('plugins', 'settings_page_cgptfc-settings'))) {
            return;
        }

        // Check if API keys are missing for the selected provider
        $provider = get_option('cgptfc_api_provider', 'openai');
        $api_key_option = ($provider === 'openai') ? 'cgptfc_api_key' : 'cgptfc_gemini_api_key';

        if (empty(get_option($api_key_option))) {
            ?>
            <div class="notice notice-warning is-dismissible">
                <p>
            <?php
            if ($provider === 'openai') {
                _e('<strong>ChatGPT & Gemini Connector:</strong> Please configure your OpenAI API key to start using the plugin.', 'chatgpt-fluent-connector');
            } else {
                _e('<strong>ChatGPT & Gemini Connector:</strong> Please configure your Gemini API key to start using the plugin.', 'chatgpt-fluent-connector');
            }
            ?>
                    <a href="<?php echo admin_url('options-general.php?page=cgptfc-settings'); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php _e('Configure Now', 'chatgpt-fluent-connector'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Enqueue admin styles
     */
    public function admin_enqueue_styles($hook) {
        // Only load on our settings page and prompt edit pages
        if ($hook === 'settings_page_cgptfc-settings' ||
                ($hook === 'post.php' && isset($_GET['post']) && get_post_type($_GET['post']) === 'cgptfc_prompt') ||
                $hook === 'post-new.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'cgptfc_prompt') {

            wp_enqueue_style(
                    'cgptfc-admin-styles',
                    CGPTFC_URL . 'assets/css/admin-styles.css',
                    array(),
                    CGPTFC_VERSION
            );
        }
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('chatgpt-fluent-connector', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Get the active AI API based on settings
     * 
     * @return object The API class instance
     */
    public function get_active_api() {
        $provider = get_option('cgptfc_api_provider', 'openai');

        if ($provider === 'gemini') {
            return $this->gemini_api;
        }

        return $this->api; // Default to OpenAI
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