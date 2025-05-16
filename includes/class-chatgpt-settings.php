<?php
/**
 * ChatGPT Settings Class - Updated to include Gemini API
 * 
 * Handles the plugin settings page and API credentials for multiple providers
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
        add_action('wp_ajax_cgptfc_test_gemini_connection', array($this, 'test_gemini_connection_ajax'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
                __('AI API Connector', 'chatgpt-fluent-connector'),
                __('AI API Connector', 'chatgpt-fluent-connector'),
                'manage_options',
                'cgptfc-settings',
                array($this, 'admin_page')
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        // General Settings
        register_setting('cgptfc_settings', 'cgptfc_api_provider', array(
            'default' => 'openai'
        ));
        register_setting('cgptfc_settings', 'cgptfc_debug_mode');

        // OpenAI Settings
        register_setting('cgptfc_settings', 'cgptfc_api_key');
        register_setting('cgptfc_settings', 'cgptfc_api_endpoint', array(
            'default' => 'https://api.openai.com/v1/chat/completions'
        ));
        register_setting('cgptfc_settings', 'cgptfc_model', array(
            'default' => 'gpt-3.5-turbo'
        ));

        // Gemini Settings
        register_setting('cgptfc_settings', 'cgptfc_gemini_api_key');
        register_setting('cgptfc_settings', 'cgptfc_gemini_api_endpoint', array(
            'default' => 'https://generativelanguage.googleapis.com/v1beta/models/'
        ));
        register_setting('cgptfc_settings', 'cgptfc_gemini_model', array(
            'default' => 'gemini-pro'
        ));

        // General Section
        add_settings_section(
                'cgptfc_general_section',
                __('General Settings', 'chatgpt-fluent-connector'),
                array($this, 'general_section_callback'),
                'cgptfc_settings'
        );

        add_settings_field(
                'cgptfc_api_provider',
                __('API Provider', 'chatgpt-fluent-connector'),
                array($this, 'api_provider_field_callback'),
                'cgptfc_settings',
                'cgptfc_general_section'
        );

        add_settings_field(
                'cgptfc_debug_mode',
                __('Debug Mode', 'chatgpt-fluent-connector'),
                array($this, 'debug_mode_field_callback'),
                'cgptfc_settings',
                'cgptfc_general_section'
        );

        // OpenAI Section
        add_settings_section(
                'cgptfc_openai_section',
                __('OpenAI API Settings', 'chatgpt-fluent-connector'),
                array($this, 'openai_section_callback'),
                'cgptfc_settings'
        );

        add_settings_field(
                'cgptfc_api_key',
                __('API Key', 'chatgpt-fluent-connector'),
                array($this, 'api_key_field_callback'),
                'cgptfc_settings',
                'cgptfc_openai_section'
        );

        add_settings_field(
                'cgptfc_api_endpoint',
                __('API Endpoint', 'chatgpt-fluent-connector'),
                array($this, 'api_endpoint_field_callback'),
                'cgptfc_settings',
                'cgptfc_openai_section'
        );

        add_settings_field(
                'cgptfc_model',
                __('GPT Model', 'chatgpt-fluent-connector'),
                array($this, 'model_field_callback'),
                'cgptfc_settings',
                'cgptfc_openai_section'
        );

        // Gemini Section
        add_settings_section(
                'cgptfc_gemini_section',
                __('Google Gemini API Settings', 'chatgpt-fluent-connector'),
                array($this, 'gemini_section_callback'),
                'cgptfc_settings'
        );

        add_settings_field(
                'cgptfc_gemini_api_key',
                __('Gemini API Key', 'chatgpt-fluent-connector'),
                array($this, 'gemini_api_key_field_callback'),
                'cgptfc_settings',
                'cgptfc_gemini_section'
        );

        add_settings_field(
                'cgptfc_gemini_api_endpoint',
                __('Gemini API Endpoint', 'chatgpt-fluent-connector'),
                array($this, 'gemini_api_endpoint_field_callback'),
                'cgptfc_settings',
                'cgptfc_gemini_section'
        );

        add_settings_field(
                'cgptfc_gemini_model',
                __('Gemini Model', 'chatgpt-fluent-connector'),
                array($this, 'gemini_model_field_callback'),
                'cgptfc_settings',
                'cgptfc_gemini_section'
        );
    }

    /**
     * General section description
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Choose which AI provider to use and configure general settings.', 'chatgpt-fluent-connector') . '</p>';
    }

    /**
     * API Provider field
     */
    public function api_provider_field_callback() {
        $provider = get_option('cgptfc_api_provider', 'openai');
        ?>
        <select name="cgptfc_api_provider" id="cgptfc_api_provider">
            <option value="openai" <?php selected($provider, 'openai'); ?>><?php echo esc_html__('OpenAI (ChatGPT)', 'chatgpt-fluent-connector'); ?></option>
            <option value="gemini" <?php selected($provider, 'gemini'); ?>><?php echo esc_html__('Google Gemini', 'chatgpt-fluent-connector'); ?></option>
        </select>
        <p class="description"><?php echo esc_html__('Select which AI provider you want to use', 'chatgpt-fluent-connector'); ?></p>
        <?php
    }

    /**
     * Debug Mode field
     */
    public function debug_mode_field_callback() {
        $debug_mode = get_option('cgptfc_debug_mode', '0');
        ?>
        <label>
            <input type="checkbox" name="cgptfc_debug_mode" value="1" <?php checked($debug_mode, '1'); ?>>
            <?php echo esc_html__('Enable debug mode', 'chatgpt-fluent-connector'); ?>
        </label>
        <p class="description"><?php echo esc_html__('When enabled, debug information will be written to the WordPress error log', 'chatgpt-fluent-connector'); ?></p>
        <?php
    }

    /**
     * OpenAI section description
     */
    public function openai_section_callback() {
        echo '<p>' . esc_html__('Enter your ChatGPT (OpenAI) API credentials below.', 'chatgpt-fluent-connector') . '</p>';
    }

    /**
     * Gemini section description
     */
    public function gemini_section_callback() {
        echo '<p>' . esc_html__('Enter your Google Gemini API credentials below.', 'chatgpt-fluent-connector') . '</p>';
    }

    /**
     * API Key field
     */
    public function api_key_field_callback() {
        $api_key = get_option('cgptfc_api_key');
        ?>
        <input type="password" name="cgptfc_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
        <p class="description">
            <?php echo esc_html__('Enter your OpenAI API key.', 'chatgpt-fluent-connector'); ?> 
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
            'gpt-3.5-turbo' => __('GPT-3.5 Turbo (4K tokens, fastest, most cost-effective)', 'chatgpt-fluent-connector'),
            'gpt-4' => __('GPT-4 (8K tokens, more powerful, more expensive)', 'chatgpt-fluent-connector'),
            'gpt-4-turbo' => __('GPT-4 Turbo (128K tokens, latest GPT-4 model)', 'chatgpt-fluent-connector'),
            'gpt-4-1106-preview' => __('GPT-4 Turbo (128K tokens, November 2023 preview)', 'chatgpt-fluent-connector'),
            'gpt-4-0613' => __('GPT-4 (8K tokens, June 2023 snapshot)', 'chatgpt-fluent-connector'),
            'gpt-4-0125-preview' => __('GPT-4 Preview (8K tokens, with advanced reasoning)', 'chatgpt-fluent-connector'),
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
     * Gemini API Key field
     */
    public function gemini_api_key_field_callback() {
        $api_key = get_option('cgptfc_gemini_api_key');
        ?>
        <input type="password" name="cgptfc_gemini_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
        <p class="description">
            <?php echo esc_html__('Enter your Google AI Gemini API key.', 'chatgpt-fluent-connector'); ?> 
            <a href="https://ai.google.dev/" target="_blank"><?php echo esc_html__('Get your API key from Google AI Studio', 'chatgpt-fluent-connector'); ?></a>
        </p>
        <?php
    }

    /**
     * Gemini API Endpoint field
     */
    public function gemini_api_endpoint_field_callback() {
        $api_endpoint = get_option('cgptfc_gemini_api_endpoint', 'https://generativelanguage.googleapis.com/v1beta/models/');
        ?>
        <input type="text" name="cgptfc_gemini_api_endpoint" value="<?php echo esc_attr($api_endpoint); ?>" class="regular-text" />
        <p class="description"><?php echo esc_html__('The default endpoint for the Gemini API', 'chatgpt-fluent-connector'); ?></p>
        <?php
    }

    /**
     * Simplified Settings with Working Model Options
     */
    public function gemini_model_field_callback() {
        $model = get_option('cgptfc_gemini_model', 'gemini-2.5-pro-preview-05-06');
        ?>
        <select name="cgptfc_gemini_model">
            <option value="gemini-2.5-pro-preview-05-06" <?php selected($model, 'gemini-2.5-pro-preview-05-06'); ?>>
                <?php echo esc_html__('Gemini 2.5 Pro (I/O edition - Recommended)', 'chatgpt-fluent-connector'); ?>
            </option>
        </select>

        <p class="description">
            <?php echo esc_html__('Using Gemini 2.5 Pro I/O Edition - our testing shows this is currently the only working model with your API key.', 'chatgpt-fluent-connector'); ?>
        </p>

        <div class="notice notice-info inline" style="margin-top: 10px;">
            <p><?php _e('Note: Based on testing, only the Gemini 2.5 Pro Preview 05-06 version is available with your API key. Other model versions may become available in the future.', 'chatgpt-fluent-connector'); ?></p>
        </div>
        <?php
    }

    /**
     * Admin page HTML - Fixed version to render sections properly
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $api_provider = get_option('cgptfc_api_provider', 'openai');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('cgptfc_settings');
                ?>

                <!-- General Settings Section - Rendered manually -->
                <div class="cgptfc-settings-section">
                    <h2><?php _e('General Settings', 'chatgpt-fluent-connector'); ?></h2>
                    <p><?php _e('Choose which AI provider to use and configure general settings.', 'chatgpt-fluent-connector'); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('API Provider', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->api_provider_field_callback(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Debug Mode', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->debug_mode_field_callback(); ?></td>
                        </tr>
                    </table>
                </div>

                <!-- Provider-specific settings sections with conditional display -->
                <div id="openai-settings" class="cgptfc-provider-settings" <?php echo ($api_provider != 'openai') ? 'style="display:none;"' : ''; ?>>
                    <h2><?php _e('OpenAI API Settings', 'chatgpt-fluent-connector'); ?></h2>
                    <p><?php _e('Enter your ChatGPT (OpenAI) API credentials below.', 'chatgpt-fluent-connector'); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('API Key', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->api_key_field_callback(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('API Endpoint', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->api_endpoint_field_callback(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('GPT Model', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->model_field_callback(); ?></td>
                        </tr>
                    </table>
                </div>

                <div id="gemini-settings" class="cgptfc-provider-settings" <?php echo ($api_provider != 'gemini') ? 'style="display:none;"' : ''; ?>>
                    <h2><?php _e('Google Gemini API Settings', 'chatgpt-fluent-connector'); ?></h2>
                    <p><?php _e('Enter your Google Gemini API credentials below.', 'chatgpt-fluent-connector'); ?></p>

                    <table class="form-table" role="presentation">
                        <tr>
                            <th scope="row"><?php _e('Gemini API Key', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->gemini_api_key_field_callback(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Gemini API Endpoint', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->gemini_api_endpoint_field_callback(); ?></td>
                        </tr>
                        <tr>
                            <th scope="row"><?php _e('Gemini Model', 'chatgpt-fluent-connector'); ?></th>
                            <td><?php $this->gemini_model_field_callback(); ?></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'chatgpt-fluent-connector')); ?>
            </form>

            <hr>

            <h2><?php echo esc_html__('Test Connection', 'chatgpt-fluent-connector'); ?></h2>
            <p><?php echo esc_html__('Click the button below to test your API connection:', 'chatgpt-fluent-connector'); ?></p>

            <div id="openai-test" <?php echo ($api_provider != 'openai') ? 'style="display:none;"' : ''; ?>>
                <button id="test-cgptfc-connection" class="button button-primary"><?php echo esc_html__('Test OpenAI Connection', 'chatgpt-fluent-connector'); ?></button>
            </div>

            <div id="gemini-test" <?php echo ($api_provider != 'gemini') ? 'style="display:none;"' : ''; ?>>
                <button id="test-gemini-connection" class="button button-primary"><?php echo esc_html__('Test Gemini Connection', 'chatgpt-fluent-connector'); ?></button>
            </div>

            <div id="connection-result" style="margin-top: 15px; padding: 15px; display: none;">
                <pre style="white-space: pre-wrap;"></pre>
            </div>

            <hr>

            <h2><?php echo esc_html__('Fluent Forms Integration', 'chatgpt-fluent-connector'); ?></h2>
            <p><?php echo esc_html__('Create AI prompts that are triggered when specific Fluent Forms are submitted:', 'chatgpt-fluent-connector'); ?></p>

            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=cgptfc_prompt')); ?>" class="button button-primary"><?php echo esc_html__('Add New Prompt', 'chatgpt-fluent-connector'); ?></a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=cgptfc_prompt')); ?>" class="button"><?php echo esc_html__('View All Prompts', 'chatgpt-fluent-connector'); ?></a>

            <script type="text/javascript">
                jQuery(document).ready(function ($) {
                    // Toggle API provider settings visibility
                    $('#cgptfc_api_provider').on('change', function () {
                        var provider = $(this).val();
                        $('.cgptfc-provider-settings').hide();
                        $('#' + provider + '-settings').show();

                        // Toggle test buttons visibility
                        $('#openai-test, #gemini-test').hide();
                        $('#' + provider + '-test').show();
                    });

                    // OpenAI test connection
                    $('#test-cgptfc-connection').on('click', function (e) {
                        e.preventDefault();

                        var resultBox = $('#connection-result');
                        resultBox.removeClass('notice-success notice-error').hide();
                        resultBox.find('pre').text('<?php echo esc_js(__('Testing OpenAI connection...', 'chatgpt-fluent-connector')); ?>');
                        resultBox.show();

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cgptfc_test_connection'
                            },
                            success: function (response) {
                                if (response.success) {
                                    resultBox.addClass('notice notice-success');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Success!', 'chatgpt-fluent-connector')); ?></strong> <?php echo esc_js(__('Connection to OpenAI API is working.', 'chatgpt-fluent-connector')); ?><br><br><?php echo esc_js(__('Response:', 'chatgpt-fluent-connector')); ?><br>' + JSON.stringify(response.data, null, 2));
                                } else {
                                    resultBox.addClass('notice notice-error');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Error:', 'chatgpt-fluent-connector')); ?></strong> ' + response.data);
                                }
                            },
                            error: function (xhr, status, error) {
                                resultBox.addClass('notice notice-error');
                                resultBox.find('pre').html('<strong><?php echo esc_js(__('Error:', 'chatgpt-fluent-connector')); ?></strong> ' + error);
                            }
                        });
                    });

                    // Gemini test connection
                    $('#test-gemini-connection').on('click', function (e) {
                        e.preventDefault();

                        var resultBox = $('#connection-result');
                        resultBox.removeClass('notice-success notice-error').hide();
                        resultBox.find('pre').text('<?php echo esc_js(__('Testing Gemini connection...', 'chatgpt-fluent-connector')); ?>');
                        resultBox.show();

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'cgptfc_test_gemini_connection'
                            },
                            success: function (response) {
                                if (response.success) {
                                    resultBox.addClass('notice notice-success');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Success!', 'chatgpt-fluent-connector')); ?></strong> <?php echo esc_js(__('Connection to Gemini API is working.', 'chatgpt-fluent-connector')); ?><br><br><?php echo esc_js(__('Response:', 'chatgpt-fluent-connector')); ?><br>' + JSON.stringify(response.data, null, 2));
                                } else {
                                    resultBox.addClass('notice notice-error');
                                    resultBox.find('pre').html('<strong><?php echo esc_js(__('Error:', 'chatgpt-fluent-connector')); ?></strong> ' + response.data);
                                }
                            },
                            error: function (xhr, status, error) {
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
     * Test OpenAI connection AJAX handler
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

    /**
     * Test Gemini connection AJAX handler
     */
    public function test_gemini_connection_ajax() {
        // Check for admin privileges
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'chatgpt-fluent-connector'));
            return;
        }

        // Get the Gemini API instance
        $main = cgptfc_main();
        if (!isset($main->gemini_api) || !is_object($main->gemini_api)) {
            wp_send_json_error(__('Gemini API class not initialized properly', 'chatgpt-fluent-connector'));
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
        $response = $main->gemini_api->make_request($messages, null, 50);

        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
            return;
        }

        wp_send_json_success($response);
    }
}
