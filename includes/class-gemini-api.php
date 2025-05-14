<?php
/**
 * Gemini API Class - Fixed for 404 Error with additional debugging
 * 
 * Handles API requests to the Google Gemini API
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Gemini_API {

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here yet
    }

    /**
     * Make a request to the Gemini API
     *
     * @param array $messages Array of message objects (role, content)
     * @param string $model Optional. The model to use. If null, uses the setting.
     * @param int $max_tokens Optional. Maximum tokens in the response.
     * @param float $temperature Optional. Temperature for response randomness.
     * @return array|WP_Error Response from API or error
     */
    public function make_request($messages, $model = null, $max_tokens = 1000, $temperature = 0.7) {
        $api_key = get_option('cgptfc_gemini_api_key');
        $debug_mode = get_option('cgptfc_debug_mode', '0');
        
        // Use the direct, explicit endpoint for Gemini API
        $api_base = 'https://generativelanguage.googleapis.com/v1beta/models/';

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Gemini API key is not set', 'chatgpt-fluent-connector'));
        }

        // Use specified model or fall back to settings
        if ($model === null) {
            $model = get_option('cgptfc_gemini_model', 'gemini-pro');
        }
        
        // If using debug mode, log the model value for verification
        if ($debug_mode === '1') {
            error_log('CGPTFC: Using Gemini model: ' . $model);
        }

        // Token limits for Gemini models
        $token_limits = [
            'gemini-pro' => 32000,
            'gemini-1.5-pro' => 1000000, // 1M token context
            'gemini-1.5-flash' => 1000000
        ];

        // Set default max token limit
        $default_limit = 32000;
        $model_limit = isset($token_limits[$model]) ? $token_limits[$model] : $default_limit;

        // Ensure max_tokens is within model limits
        $max_tokens = min(intval($max_tokens), $model_limit);

        // Log the model and max tokens for debugging
        if ($debug_mode === '1') {
            error_log('CGPTFC: Making Gemini API request with model: ' . $model . ', max_tokens: ' . $max_tokens);
        }

        // Convert OpenAI message format to Gemini format
        $gemini_messages = $this->convert_messages_to_gemini_format($messages);

        // Build full endpoint URL with hardcoded structure to ensure correctness
        $full_endpoint = $api_base . $model . ':generateContent' . '?key=' . $api_key;
        
        if ($debug_mode === '1') {
            error_log('CGPTFC: Full Gemini API endpoint: ' . $full_endpoint);
        }

        $headers = array(
            'Content-Type' => 'application/json'
        );

        $body = array(
            'contents' => $gemini_messages,
            'generationConfig' => array(
                'maxOutputTokens' => intval($max_tokens),
                'temperature' => floatval($temperature),
                'topP' => 0.95,
                'topK' => 40
            )
        );

        if ($debug_mode === '1') {
            error_log('CGPTFC: Gemini API Request body: ' . wp_json_encode($body));
        }

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 60 // Increase timeout for larger responses
        );

        // Make the API request
        $response = wp_remote_post($full_endpoint, $args);

        // Check for WordPress request errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('CGPTFC: Gemini API WordPress Request Error: ' . $error_message);
            return $response;
        }

        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body_raw = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body_raw, true);

        // Always log the full response in debug mode
        if ($debug_mode === '1') {
            error_log('CGPTFC: Gemini API Response Code: ' . $response_code);
            error_log('CGPTFC: Gemini API Raw Response: ' . $response_body_raw);
        }

        // Handle HTTP errors
        if ($response_code !== 200) {
            // Try to extract error message from response if possible
            $error_message = '';
            if (isset($response_body['error']['message'])) {
                $error_message = $response_body['error']['message'];
            } else {
                $error_message = sprintf(__('Unknown error (HTTP %s)', 'chatgpt-fluent-connector'), $response_code);
            }
            
            // Log error details
            error_log('CGPTFC: Gemini API Error: ' . $error_message);
            
            // For 404 specifically, add more information as this is likely a model not found issue
            if ($response_code === 404) {
                error_log('CGPTFC: 404 Error - Model "' . $model . '" not found. Please check the model name and API version.');
                return new WP_Error('api_error', $error_message . ' - Model "' . $model . '" not found. Available models may include: "gemini-pro", "gemini-1.5-pro".');
            }
            
            return new WP_Error('api_error', $error_message);
        }

        // Success case
        if ($debug_mode === '1') {
            error_log('CGPTFC: Gemini API Response: ' . wp_json_encode($response_body));
        }

        return $response_body;
    }

    /**
     * Convert OpenAI format messages to Gemini format
     * 
     * @param array $messages Messages in OpenAI format
     * @return array Messages in Gemini format
     */
    private function convert_messages_to_gemini_format($messages) {
        $gemini_contents = [];
        $system_content = '';
        
        // First, extract system message if present
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system_content = $message['content'];
                break;
            }
        }

        // Then process user and assistant messages
        foreach ($messages as $message) {
            // Skip system messages as they're handled separately
            if ($message['role'] === 'system') {
                continue;
            }
            
            $gemini_role = ($message['role'] === 'assistant') ? 'model' : 'user';
            
            $gemini_contents[] = [
                'role' => $gemini_role,
                'parts' => [
                    ['text' => $message['content']]
                ]
            ];
        }
        
        // If we have a system message, prepend it to the first user message
        // or add it as a user message if there are no messages yet
        if (!empty($system_content)) {
            if (!empty($gemini_contents) && $gemini_contents[0]['role'] === 'user') {
                // Prepend to first user message
                $gemini_contents[0]['parts'][0]['text'] = "System instruction: " . $system_content . "\n\n" . $gemini_contents[0]['parts'][0]['text'];
            } else {
                // Add as a new first message
                array_unshift($gemini_contents, [
                    'role' => 'user',
                    'parts' => [
                        ['text' => "System instruction: " . $system_content]
                    ]
                ]);
            }
        }
        
        return $gemini_contents;
    }

    /**
     * Get the content from the API response
     *
     * @param array $response The Gemini API response array
     * @return string|WP_Error The response content or error
     */
    public function get_response_content($response) {
        if (is_wp_error($response)) {
            return $response;
        }

        // Check if we have a valid response with candidates
        if (!isset($response['candidates']) || 
            !isset($response['candidates'][0]) || 
            !isset($response['candidates'][0]['content']) ||
            !isset($response['candidates'][0]['content']['parts']) ||
            !isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            
            return new WP_Error('invalid_response', __('Invalid response from Gemini API', 'chatgpt-fluent-connector'));
        }

        return $response['candidates'][0]['content']['parts'][0]['text'];
    }

    /**
     * Process a form submission with a prompt
     *
     * @param int $prompt_id The prompt post ID
     * @param array $form_data The form submission data
     * @return string|WP_Error The response content or error
     */
    public function process_form_with_prompt($prompt_id, $form_data) {
        $debug_mode = get_option('cgptfc_debug_mode', '0');
        if ($debug_mode === '1') {
            error_log('CGPTFC: Processing prompt ID with Gemini: ' . $prompt_id);
        }

        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
        $temperature = get_post_meta($prompt_id, '_cgptfc_temperature', true);
        $max_tokens = get_post_meta($prompt_id, '_cgptfc_max_tokens', true);
        $prompt_type = get_post_meta($prompt_id, '_cgptfc_prompt_type', true);
        $model = get_option('cgptfc_gemini_model', 'gemini-pro');

        // Set default prompt type if not set
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        // Check max tokens based on model
        $token_limits = [
            'gemini-pro' => 32000,
            'gemini-1.5-pro' => 1000000,
            'gemini-1.5-flash' => 1000000
        ];

        // Set default max token limit
        $default_limit = 32000;
        $model_limit = isset($token_limits[$model]) ? $token_limits[$model] : $default_limit;

        // Ensure max_tokens is within model limits
        if (intval($max_tokens) > $model_limit) {
            $max_tokens = $model_limit;
            if ($debug_mode === '1') {
                error_log('CGPTFC: Capped max_tokens to Gemini model limit: ' . $model_limit);
            }
        }

        // Prepare the user prompt based on prompt type
        $user_prompt = '';
        if ($prompt_type === 'all_form_data') {
            // Use all form data - this function should be implemented similarly to OpenAI version
            $user_prompt = $this->format_all_form_data($form_data, $prompt_id);
        } else {
            // Use custom template
            if (empty($user_prompt_template)) {
                return new WP_Error('no_prompt_template', __('No user prompt template configured', 'chatgpt-fluent-connector'));
            }

            // Replace placeholders in user prompt
            $user_prompt = $user_prompt_template;

            // Replace field placeholders with actual values
            foreach ($form_data as $field_key => $field_value) {
                // Skip if field_key is not a scalar (string/number)
                if (!is_scalar($field_key)) {
                    continue;
                }

                // Handle array values (like checkboxes)
                if (is_array($field_value)) {
                    $field_value = implode(', ', $field_value);
                } elseif (!is_scalar($field_value)) {
                    // Skip non-scalar values
                    continue;
                }

                $user_prompt = str_replace('{' . $field_key . '}', $field_value, $user_prompt);
            }

            // Check for any remaining placeholders and replace with empty string
            $user_prompt = preg_replace('/\{[^}]+\}/', '', $user_prompt);
        }

        if (empty($user_prompt)) {
            return new WP_Error('empty_prompt', __('User prompt is empty after processing', 'chatgpt-fluent-connector'));
        }

        // Tell Gemini it can use HTML in responses
        if (!empty($system_prompt)) {
            $system_prompt .= "\n\nYou can use HTML formatting in your response if needed for better presentation, such as <h3>, <p>, <ul>, <li>, <strong>, <em>, etc.";
        } else {
            $system_prompt = "You are a helpful assistant. You can use HTML formatting in your response if needed for better presentation, such as <h3>, <p>, <ul>, <li>, <strong>, <em>, etc.";
        }

        // Apply HTML template filter - this will add the template if enabled
        $user_prompt = apply_filters('cgptfc_process_form_with_prompt', $user_prompt, $prompt_id, $form_data);

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

        if ($debug_mode === '1') {
            error_log('CGPTFC: Sending prompt to Gemini with ' . count($messages) . ' messages');
            error_log('CGPTFC: User prompt: ' . substr($user_prompt, 0, 200) . '...');
        }

        // Make the API request
        $response = $this->make_request(
            $messages,
            $model,
            !empty($max_tokens) ? intval($max_tokens) : 1000,
            !empty($temperature) ? floatval($temperature) : 0.7
        );

        if (is_wp_error($response)) {
            error_log('CGPTFC: Error in Gemini API response: ' . $response->get_error_message());
            return $response;
        }

        $content = $this->get_response_content($response);
        if ($debug_mode === '1') {
            error_log('CGPTFC: Got Gemini response of length: ' . strlen($content));
        }

        return $content;
    }

    /**
     * Format all form data into a structured text for Gemini
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