<?php
/**
 * ChatGPT Settings Class
 * 
 * Handles the plugin settings page and API credentials
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Settings {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add menu item
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add AJAX handlers
        add_action('wp_ajax_cgptfc_test_connection', array($this, 'test_connection_ajax'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('ChatGPT Connector', 'chatgpt-fluent-connector'),
            __('ChatGPT Connector', 'chatgpt-fluent-connector'),
            'manage_options',
            'cgptfc-settings',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cgptfc_settings', 'cgptfc_api_key');
        register_setting('cgptfc_settings', 'cgptfc_api_endpoint', array(
            'default' => 'https://api.openai.com/v1/chat/completions'
        ));
        register_setting('cgptfc_settings', 'cgptfc_model', array(
            'default' => 'gpt-3.5-turbo'
        ));
        
        add_settings_section(
            'cgptfc_settings_section',
            __('API Settings', 'chatgpt-fluent-connector'),
            array($this, 'settings_section_callback'),
            'cgptfc_settings'
        );
        
        add_settings_field(
            'cgptfc_api_key',
            __('API Key', 'chatgpt-fluent-connector'),
            array($this, 'api_key_field_callback'),
            'cgptfc_settings',
            'cgptfc_settings_section'
        );
        
        add_settings_field(
            'cgptfc_api_endpoint',
            __('API Endpoint', 'chatgpt-fluent-connector'),
            array($this, 'api_endpoint_field_callback'),
            'cgptfc_settings',
            'cgptfc_settings_section'
        );
        
        add_settings_field(
            'cgptfc_model',
            __('GPT Model', 'chatgpt-fluent-connector'),
            array($this, 'model_field_callback'),
            'cgptfc_settings',
            'cgptfc_settings_section'
        );
    }
    
    /**
     * Settings section description
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__('Enter your ChatGPT API credentials below.', 'chatgpt-fluent-connector') . '</p>';
    }
    
    /**
     * API Key field
     */
    public function api_key_field_callback() {
        $api_key = get_option('cgptfc_api_key');
        ?>
        <input type="password" name="cgptfc_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
        <p class="description">
            <?php echo esc_html__('Enter your ChatGPT API key.', 'chatgpt-fluent-connector'); ?> 
            <a href="https://platform.openai.com/api-keys" target="_blank"><?php echo esc_html__('Get your API key', 'chatgpt-fluent-connector'); ?></a>
        </p>
        <?php
    }
    
    /**
     * API Endpoint field
     */
    public function api_endpoint_field_callback() {
        $api_endpoint = get_option('cgptfc_api_endpoint', 'https://api.openai.com/v1/chat/completions');
        ?>
        <input type="text" name="cgptfc_api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" class="regular-text" />
        <p class="description"><?php echo esc_html__('The default endpoint is the ChatGPT completions API', 'chatgpt-fluent-connector'); ?></p>
        <?php
    }
    
    /**
     * Model field
     */
    public function model_field_callback() {
        $model = get_option('cgptfc_model', 'gpt-3.5-turbo');
        $models = array(
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo (Fastest, most cost-effective)', 'chatgpt-fluent-connector'),
            'gpt-4' => __('GPT-4 (More powerful, more expensive)', 'chatgpt-fluent-connector'),
            'gpt-4-turbo' => __('GPT-4 Turbo (Latest model)', 'chatgpt-fluent-connector'),
        );
        ?>
        <select name="cgptfc_model">
            <?php foreach ($models as $model_id => $model_name) : ?>
                <option value="<?php echo esc_attr($model_id); ?>" <?php selected($model, $model_id); ?>><?php echo esc_html($model_name); ?></option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php echo esc_html__('Select which OpenAI model to use', 'chatgpt-fluent-connector'); ?></p>
        <?php
    }
    
    /**
     * Admin page HTML
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('cgptfc_settings');
                do_settings_sections('cgptfc_settings');
                submit_button(__('Save Settings', 'chatgpt-fluent-connector'));
                ?>
            </form>
            
            <hr>
            
            <h2><?php echo esc_html__('Test Connection', 'chatgpt-fluent-connector'); ?></h2>
            <p><?php echo esc_html__('Click the button below to test your ChatGPT API connection:', 'chatgpt-fluent-connector'); ?></p>
            
            <button id="test-cgptfc-connection" class="button button-primary"><?php echo esc_html__('Test Connection', 'chatgpt-fluent-connector'); ?></button>
            
            <div id="connection-result" style="margin-top: 15px; padding: 15px; display: none;">
                <pre style="white-space: pre-wrap;"></pre>
            </div>
            
            <hr>
            
            <h2><?php echo esc_html__('Fluent Forms Integration', 'chatgpt-fluent-connector'); ?></h2>
            <p><?php echo esc_html__('Create ChatGPT prompts that are triggered when specific Fluent Forms are submitted:', 'chatgpt-fluent-connector'); ?></p>
            
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=cgptfc_prompt')); ?>" class="button button-primary"><?php echo esc_html__('Add New Prompt', 'chatgpt-fluent-connector'); ?></a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=cgptfc_prompt')); ?>" class="button"><?php echo esc_html__('View All Prompts', 'chatgpt-fluent-connector'); ?></a>
            
            <script type="text/javascript">
                jQuery(document).ready(function($) {
                    $('#test-cgptfc-connection').on('click', function(e) {
                        e.preventDefault();
                        
                        var resultBox = $('#connection-result');
                        resultBox.removeClass('notice-success notice-error').hide();
                        resultBox.find('pre').text('<?php echo esc_js(__('Testing connection...', 'chatgpt-fluent-connector')); ?>');
                        resultBox.show();
                        
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cgptfc_test_connection'
                            },
                            success: function(response) {
                                if (response.success) {
                                    resultBox.addClass('notice notice-success');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Success!', 'chatgpt-fluent-connector')); ?></strong> <?php echo esc_js(__('Connection to ChatGPT API is working.', 'chatgpt-fluent-connector')); ?><br><br><?php echo esc_js(__('Response:', 'chatgpt-fluent-connector')); ?><br>' + JSON.stringify(response.data, null, 2));
                                } else {
                                    resultBox.addClass('notice notice-error');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Error:', 'chatgpt-fluent-connector')); ?></strong> ' + response.data);
                                }
                            },
                            error: function(xhr, status, error) {
                                resultBox.addClass('notice notice-error');
                                resultBox.find('pre').html('<strong><?php echo esc_js(__('Error:', 'chatgpt-fluent-connector')); ?></strong> ' + error);
                            }
                        });
                    });
                });
            </script>
        </div>
        <?php
    }
    
    /**
     * Test connection AJAX handler
     */
    public function test_connection_ajax() {
        // Check for admin privileges
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'chatgpt-fluent-connector'));
            return;
        }
        
        // Get the API instance from the main plugin class
        $main = cgptfc_main();
        if (!isset($main->api) || !is_object($main->api)) {
            wp_send_json_error(__('API class not initialized properly', 'chatgpt-fluent-connector'));
            return;
        }
        
        // Prepare test message
        $messages = array(
            array(
                'role' => 'user',
                'content' => __('Hello! This is a test connection from WordPress.', 'chatgpt-fluent-connector')
            )
        );
        
        // Make the API request
        $response = $main->api->make_request($messages, null, 50);
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }
        
        wp_send_json_success($response);
    }
}