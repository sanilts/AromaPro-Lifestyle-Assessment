<?php

/**
 * ChatGPT API Class
 * 
 * Handles API requests to the ChatGPT API
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_API {

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here yet
    }

    /**
     * Make a request to the ChatGPT API
     *
     * @param array $messages Array of message objects (role, content)
     * @param string $model Optional. The model to use. If null, uses the setting.
     * @param int $max_tokens Optional. Maximum tokens in the response.
     * @param float $temperature Optional. Temperature for response randomness.
     * @return array|WP_Error Response from API or error
     */

    /**
     * Make a request to the ChatGPT API
     *
     * @param array $messages Array of message objects (role, content)
     * @param string $model Optional. The model to use. If null, uses the setting.
     * @param int $max_tokens Optional. Maximum tokens in the response.
     * @param float $temperature Optional. Temperature for response randomness.
     * @return array|WP_Error Response from API or error
     */
    public function make_request($messages, $model = null, $max_tokens = 1000, $temperature = 0.7) {
        // Get API credentials
        $api_key = get_option('cgptfc_api_key');
        $api_endpoint = get_option('cgptfc_api_endpoint', 'https://api.openai.com/v1/chat/completions');

        if (empty($api_key)) {
            error_log('CGPTFC: API key is not set');
            return new WP_Error('no_api_key', __('API key is not set', 'chatgpt-fluent-connector'));
        }

        // Use specified model or fall back to settings
        if ($model === null) {
            $model = get_option('cgptfc_model', 'gpt-3.5-turbo');
        }

        error_log("CGPTFC: Making API request with model: {$model}, max_tokens: {$max_tokens}");

        // Define model-specific token limits
        $model_token_limits = array(
            'gpt-3.5-turbo' => 4096,
            'gpt-4' => 8192,
            'gpt-4-0613' => 8192,
            'gpt-4-0125-preview' => 8192,
            'gpt-4-turbo' => 128000,
            'gpt-4-1106-preview' => 128000,
            // Default fallback for unknown models
            'default' => 4096
        );

        // Get the appropriate token limit for this model
        $model_limit = isset($model_token_limits[$model]) ? $model_token_limits[$model] : $model_token_limits['default'];

        // Ensure max_tokens doesn't exceed the model's limit
        $max_tokens = intval($max_tokens);
        if ($max_tokens > $model_limit) {
            $max_tokens = $model_limit;
        }

        // Create headers
        $headers = array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        );

        // Create request body
        $body = array(
            'model' => $model,
            'messages' => $messages,
            'max_tokens' => $max_tokens,
            'temperature' => floatval($temperature)
        );

        // Add model-specific parameters
        if (strpos($model, 'gpt-4') === 0) {
            // Add specific parameters for GPT-4 models if they are different
            $body['top_p'] = 0.95; // Higher top_p for more diverse outputs with advanced models
        }

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 120 // Increased timeout for larger responses (2 minutes)
        );

        $response = wp_remote_post($api_endpoint, $args);

        if (is_wp_error($response)) {
            error_log('CGPTFC: API Error: ' . $response->get_error_message());
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code !== 200) {
            $error_message = isset($response_body['error']['message']) ? $response_body['error']['message'] : sprintf(__('Unknown error (HTTP %s)', 'chatgpt-fluent-connector'), $response_code);
            error_log('CGPTFC: Error processing prompt: ' . $error_message);
            return new WP_Error('api_error', $error_message);
        }

        return $response_body;
    }

    /**
     * Get the content from the API response
     *
     * @param array $response The API response array
     * @return string|WP_Error The response content or error
     */
    public function get_response_content($response) {
        if (is_wp_error($response)) {
            return $response;
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            return new WP_Error('invalid_response', __('Invalid response from API', 'chatgpt-fluent-connector'));
        }

        return $response['choices'][0]['message']['content'];
    }

    /**
     * Process a form submission with a prompt - Updated to properly handle HTML
     *
     * @param int $prompt_id The prompt post ID
     * @param array $form_data The form submission data
     * @return string|WP_Error The response content or error
     */
    /**
     * Process a form submission with a prompt
     *
     * @param int $prompt_id The prompt post ID
     * @param array $form_data The form submission data
     * @return string|WP_Error The response content or error
     */

    /**
     * Process a form submission with a prompt
     *
     * @param int $prompt_id The prompt post ID
     * @param array $form_data The form submission data
     * @return string|WP_Error The response content or error
     */
    public function process_form_with_prompt($prompt_id, $form_data) {
        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
        $temperature = get_post_meta($prompt_id, '_cgptfc_temperature', true);
        $max_tokens = get_post_meta($prompt_id, '_cgptfc_max_tokens', true);
        $prompt_type = get_post_meta($prompt_id, '_cgptfc_prompt_type', true);

        // Get selected model for this prompt (if you've added per-prompt model selection)
        $selected_model = get_post_meta($prompt_id, '_cgptfc_model', true);

        // If no model specified for this prompt, use global setting
        if (empty($selected_model)) {
            $selected_model = get_option('cgptfc_model', 'gpt-3.5-turbo');
        }

        error_log("CGPTFC: Making API request with max_tokens: " . intval($max_tokens));

        // Set default prompt type if not set
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        // [... existing code for preparing the user prompt ...]
        // Tell ChatGPT it can use HTML in responses
        if (!empty($system_prompt)) {
            $system_prompt .= "\n\nYou can use HTML formatting in your response if needed for better presentation, such as <h3>, <p>, <ul>, <li>, <strong>, <em>, etc.";
        } else {
            $system_prompt = "You are a helpful assistant. You can use HTML formatting in your response if needed for better presentation, such as <h3>, <p>, <ul>, <li>, <strong>, <em>, etc.";
        }

        // Prepare the messages
        $messages = array();

        if (!empty($system_prompt)) {
            $messages[] = array(
                'role' => 'system',
                'content' => $system_prompt
            );
        }

        $messages[] = array(
            'role' => 'user',
            'content' => $user_prompt
        );

        // Ensure max_tokens is a valid integer
        $max_tokens_value = !empty($max_tokens) ? intval($max_tokens) : 1000;

        // Make the API request
        $response = $this->make_request(
                $messages,
                $selected_model,
                $max_tokens_value,
                !empty($temperature) ? floatval($temperature) : 0.7
        );

        if (is_wp_error($response)) {
            error_log('CGPTFC: Error processing prompt: ' . $response->get_error_message());
            return $response;
        }

        $content = $this->get_response_content($response);
        return $content;
    }

    /**
     * Format all form data into a structured text for ChatGPT
     *
     * @param array $form_data The form data
     * @param int $prompt_id The prompt ID (for getting form field labels)
     * @return string Formatted form data as text
     */
    private function format_all_form_data($form_data, $prompt_id) {
        $output = __('Here is the submitted form data:', 'chatgpt-fluent-connector') . "\n\n";

        // Get field labels if possible
        $field_labels = $this->get_form_field_labels($prompt_id);

        // Format each form field
        foreach ($form_data as $field_key => $field_value) {
            // Skip if field_key is not a scalar or starts with '_'
            if (!is_scalar($field_key) || strpos($field_key, '_') === 0) {
                continue;
            }

            // Get label if available, otherwise use field key
            $label = isset($field_labels[$field_key]) ? $field_labels[$field_key] : $field_key;

            // Format value
            if (is_array($field_value)) {
                $field_value = implode(', ', $field_value);
            } elseif (!is_scalar($field_value)) {
                // Skip non-scalar values
                continue;
            }

            // Add to output
            $output .= $label . ': ' . $field_value . "\n";
        }

        $output .= "\n" . __('Please analyze this information and provide a response. You can use HTML formatting in your response for better presentation.', 'chatgpt-fluent-connector');
        return $output;
    }

    /**
     * Get form field labels from a selected form
     *
     * @param int $prompt_id The prompt ID
     * @return array Associative array of field keys and labels
     */
    private function get_form_field_labels($prompt_id) {
        $field_labels = array();
        $form_id = get_post_meta($prompt_id, '_cgptfc_fluent_form_id', true);

        if (empty($form_id) || !function_exists('wpFluent')) {
            return $field_labels;
        }

        // Get the form structure
        $form = wpFluent()->table('fluentform_forms')
                ->where('id', $form_id)
                ->first();

        if ($form && !empty($form->form_fields)) {
            $formFields = json_decode($form->form_fields, true);

            if (!empty($formFields['fields'])) {
                foreach ($formFields['fields'] as $field) {
                    if (!empty($field['element']) && !empty($field['attributes']['name'])) {
                        $field_name = $field['attributes']['name'];
                        $field_label = !empty($field['settings']['label']) ? $field['settings']['label'] : $field_name;
                        $field_labels[$field_name] = $field_label;
                    }
                }
            }
        }

        return $field_labels;
    }
}
