<?php

/**
 * Google Gemini API Class - FIXED with Working Chunking Support
 * 
 * Handles API requests to the Google Gemini API and tracks token usage
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SFAIC_Gemini_API {

    /**
     * Last API response for token tracking
     */
    private $last_response = null;
    private $last_request_json = null;
    private $last_response_json = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Nothing to do here yet
    }

    /**
     * Fix encoding issues in text
     */
    private function fix_encoding($text) {
        if (!is_string($text)) {
            return $text;
        }

        // First, preserve emojis by converting them to placeholders
        $emoji_pattern = '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F000}-\x{1F02F}]|[\x{1F0A0}-\x{1F0FF}]|[\x{1F100}-\x{1F1FF}]|[\x{FE00}-\x{FE0F}]|[\x{1F200}-\x{1F2FF}]|[\x{E0020}-\x{E007F}]/u';

        $emoji_map = array();
        $placeholder_index = 0;

        // Replace emojis with placeholders
        $text = preg_replace_callback($emoji_pattern, function ($match) use (&$emoji_map, &$placeholder_index) {
            $placeholder = "###EMOJI_" . $placeholder_index . "###";
            $emoji_map[$placeholder] = $match[0];
            $placeholder_index++;
            return $placeholder;
        }, $text);

        // Fix double-encoded UTF-8 (UTF-8 interpreted as Latin-1 and re-encoded)
        $replacements = array(
            "\xC3\x83\xC2\xA9" => "\xC3\xA9", // é
            "\xC3\x83\xC2\xA8" => "\xC3\xA8", // è
            "\xC3\x83\xC2\xAB" => "\xC3\xAB", // ë
            "\xC3\x83\xC2\xA2" => "\xC3\xA2", // â
            "\xC3\x83\xC2\xB4" => "\xC3\xB4", // ô
            "\xC3\x83\xC2\xAE" => "\xC3\xAE", // î
            "\xC3\x83\xC2\xA7" => "\xC3\xA7", // ç
            "\xC3\x83\xC2\xA0" => "\xC3\xA0", // à
            "\xC3\x83\xC2\xB9" => "\xC3\xB9", // ù
            "\xC3\x83\xE2\x80\xB0" => "\xC3\x89", // É
            "\xC3\x83\xE2\x82\xAC" => "\xC3\x80", // À
            "\xC3\x83\xC2\xAA" => "\xC3\xAA", // ê
            "\xC3\x83\xC2\xAF" => "\xC3\xAF", // ï
            "\xC3\x83\xC2\xBC" => "\xC3\xBC", // ü
            "\xC3\x83\xC2\xB6" => "\xC3\xB6", // ö
            "\xC3\x83\xC2\xA4" => "\xC3\xA4", // ä
            "\xC3\x83\xC2\xB1" => "\xC3\xB1", // ñ
            "\xC3\x83\xC6\x92" => "\xC3\x83", // Ã
            "\xC3\x83\xC2\xA1" => "\xC3\xA1", // á
            "\xC3\x83\xC2\xAD" => "\xC3\xAD", // í
            "\xC3\x83\xC2\xB3" => "\xC3\xB3", // ó
            "\xC3\x83\xC2\xBA" => "\xC3\xBA", // ú
        );

        // Also handle text representations
        $text_replacements = array(
            'Ã©' => 'é',
            'Ã¨' => 'è',
            'Ã«' => 'ë',
            'Ã¢' => 'â',
            'Ã´' => 'ô',
            'Ã®' => 'î',
            'Ã§' => 'ç',
            'Ã ' => 'à',
            'Ã¹' => 'ù',
            'Ã‰' => 'É',
            'Ã€' => 'À',
            'Ãª' => 'ê',
            'Ã¯' => 'ï',
            'Ã¼' => 'ü',
            'Ã¶' => 'ö',
            'Ã¤' => 'ä',
            'Ã±' => 'ñ',
            'Ãƒ' => 'Ã',
            'Ã¡' => 'á',
            'Ã­' => 'í',
            'Ã³' => 'ó',
            'Ãº' => 'ú',
        );

        // Apply replacements
        $text = str_replace(array_keys($replacements), array_values($replacements), $text);
        $text = str_replace(array_keys($text_replacements), array_values($text_replacements), $text);

        // Decode HTML entities if present
        if (strpos($text, '&') !== false) {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Restore emojis from placeholders
        foreach ($emoji_map as $placeholder => $emoji) {
            $text = str_replace($placeholder, $emoji, $text);
        }

        // Final cleanup - remove any remaining invalid UTF-8 sequences
        if (function_exists('iconv')) {
            $cleaned = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
            if ($cleaned !== false) {
                $text = $cleaned;
            }
        }

        return $text;
    }

    /**
     * Get token usage from last API call
     */
    public function get_last_token_usage() {
        if ($this->last_response && isset($this->last_response['usageMetadata'])) {
            // Handle both old and new token structures
            $prompt_tokens = $this->last_response['usageMetadata']['promptTokenCount'] ?? 0;
            $completion_tokens = $this->last_response['usageMetadata']['candidatesTokenCount'] ?? 0;

            // For Gemini 2.5, if candidatesTokenCount is not present, calculate from total - prompt
            if ($completion_tokens === 0 && isset($this->last_response['usageMetadata']['totalTokenCount'])) {
                $total_tokens = $this->last_response['usageMetadata']['totalTokenCount'] ?? 0;
                $completion_tokens = $total_tokens - $prompt_tokens;
            }

            return array(
                'prompt_tokens' => $prompt_tokens,
                'completion_tokens' => $completion_tokens,
                'total_tokens' => $this->last_response['usageMetadata']['totalTokenCount'] ?? ($prompt_tokens + $completion_tokens)
            );
        }
        return array();
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
        $api_key = get_option('sfaic_gemini_api_key');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Gemini API key is not set', 'chatgpt-fluent-connector'));
        }

        // Use specified model or fall back to settings
        if ($model === null) {
            $model = get_option('sfaic_gemini_model', 'gemini-1.5-pro-latest');
        }

        // Handle different Gemini model formats and endpoints
        $api_model = $model;
        $api_version = 'v1beta'; // Default to v1beta
        
        // Model mapping for different Gemini versions
        $model_mapping = array(
            // Gemini 1.5 models
            'gemini-1.5-pro' => 'gemini-1.5-pro-latest',
            'gemini-1.5-flash' => 'gemini-1.5-flash-latest',
            // Gemini 2.0 models
            'gemini-2.0-flash' => 'gemini-2.0-flash-exp',
            'gemini-2.0-flash-latest' => 'gemini-2.0-flash-exp',
            // Gemini 2.5 models - these will use the actual model names
            'gemini-2.5-flash' => 'gemini-2.5-flash',
            'gemini-2.5-flash-latest' => 'gemini-2.5-flash',
            'gemini-2.5-pro' => 'gemini-2.5-pro',
            'gemini-2.5-pro-latest' => 'gemini-2.5-pro',
            // Legacy models (deprecated)
            'gemini-pro' => 'gemini-1.5-pro-latest',
        );

        // Check if model needs mapping
        if (isset($model_mapping[$model])) {
            $api_model = $model_mapping[$model];
        }

        // If somehow we still have an empty or invalid model, use default
        if (empty($api_model) || $api_model === 'gemini-pro') {
            $api_model = 'gemini-1.5-pro-latest';
        }

        // Check if trying to use 2.5 models
        $is_25_model = (strpos($api_model, 'gemini-2.5') !== false);

        // Try different API versions for 2.5 models
        if ($is_25_model) {
            // First try v1 endpoint for 2.5 models
            $api_version = 'v1';
        }

        // Token limits for Gemini models (OUTPUT limits)
        $token_limits = [
            'gemini-1.5-pro-latest' => 8192,
            'gemini-1.5-flash-latest' => 8192,
            'gemini-2.0-flash-exp' => 8192,
            'gemini-2.5-flash' => 8192,
            'gemini-2.5-pro' => 8192,
            'gemini-exp-1219' => 8192,
        ];

        // Set default max token limit for Gemini (output limit)
        $model_limit = isset($token_limits[$api_model]) ? $token_limits[$api_model] : 8192;

        // Ensure max_tokens is within output limits
        $max_tokens = min(intval($max_tokens), $model_limit);

        // Ensure minimum tokens for proper response
        if ($max_tokens < 50) {
            $max_tokens = 50;
        }

        // Build the API endpoint
        $api_endpoint = 'https://generativelanguage.googleapis.com/' . $api_version . '/models/' . $api_model . ':generateContent?key=' . $api_key;

        // Convert messages and ensure proper encoding
        $gemini_contents = $this->convert_messages_to_gemini_format($messages);

        // Fix encoding in contents before sending
        foreach ($gemini_contents as &$content) {
            if (isset($content['parts'])) {
                foreach ($content['parts'] as &$part) {
                    if (isset($part['text'])) {
                        // Ensure text is properly UTF-8 encoded
                        $part['text'] = $this->fix_encoding($part['text']);
                    }
                }
            }
        }

        $headers = array(
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept' => 'application/json',
            'Accept-Charset' => 'utf-8'
        );

        $body = array(
            'contents' => $gemini_contents,
            'generationConfig' => array(
                'temperature' => floatval($temperature),
                'maxOutputTokens' => intval($max_tokens),
                'topP' => 0.95,
                'topK' => 40
            ),
            'safetySettings' => array(
                array(
                    'category' => 'HARM_CATEGORY_HATE_SPEECH',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                ),
                array(
                    'category' => 'HARM_CATEGORY_HARASSMENT',
                    'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                )
            )
        );

        // Store the complete request JSON
        $this->last_request_json = wp_json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Encode with proper UTF-8 handling
        $json_body = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Double-check the JSON is valid UTF-8
        if (!mb_check_encoding($json_body, 'UTF-8')) {
            $json_body = mb_convert_encoding($json_body, 'UTF-8', 'UTF-8');
        }

        $args = array(
            'headers' => $headers,
            'body' => $json_body,
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 600,
            'httpversion' => '1.1',
            'sslverify' => true
        );

        // Make the API request
        $response = wp_remote_post($api_endpoint, $args);

        // Check for WordPress request errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('CGPTFC: Gemini API WordPress Request Error: ' . $error_message);
            return $response;
        }

        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body_raw = wp_remote_retrieve_body($response);

        // Store the response JSON
        $this->last_response_json = $response_body_raw;

        // Fix encoding before JSON decode
        $response_body_raw = $this->fix_encoding($response_body_raw);

        $response_body = json_decode($response_body_raw, true);

        // Store the response for token tracking
        $this->last_response = $response_body;

        // Handle HTTP errors
        if ($response_code !== 200) {
            // Try to extract error message from response if possible
            $error_message = '';
            if (isset($response_body['error']['message'])) {
                $error_message = $response_body['error']['message'];

                // Handle specific model not found errors
                if ((strpos($error_message, 'not found') !== false ||
                        strpos($error_message, 'is not found') !== false) &&
                        strpos($error_message, 'models/') !== false) {

                    // Special handling for 2.5 models
                    if (strpos($api_model, 'gemini-2.5') !== false) {
                        // If 2.5 model not available, try 2.0
                        if (strpos($api_model, 'flash') !== false) {
                            return $this->make_request($messages, 'gemini-2.0-flash-exp', $max_tokens, $temperature);
                        } else {
                            // Fall back to 1.5 Pro for Pro models
                            return $this->make_request($messages, 'gemini-1.5-pro-latest', $max_tokens, $temperature);
                        }
                    }

                    // If the requested model is not available, try fallback to 1.5 Pro
                    if ($api_model !== 'gemini-1.5-pro-latest') {
                        // Update the model in settings to avoid repeated failures
                        update_option('sfaic_gemini_model', 'gemini-1.5-pro-latest');

                        // Retry with Gemini 1.5 Pro
                        return $this->make_request($messages, 'gemini-1.5-pro-latest', $max_tokens, $temperature);
                    }
                }
            } elseif (is_string($response_body_raw)) {
                // Sometimes error comes as plain text
                $error_message = $response_body_raw;
            } else {
                $error_message = sprintf(__('Unknown error (HTTP %s)', 'chatgpt-fluent-connector'), $response_code);
            }

            // Log error details
            error_log('CGPTFC: Gemini API Error: ' . $error_message);

            // Common error fixes with better messages
            if (strpos($error_message, 'API key not valid') !== false) {
                return new WP_Error('api_error', __('Invalid Gemini API key. Please check your API key in settings.', 'chatgpt-fluent-connector'));
            } elseif (strpos($error_message, 'models/') !== false &&
                    (strpos($error_message, 'not found') !== false ||
                    strpos($error_message, 'is not found') !== false)) {
                return new WP_Error('api_error',
                        __('Model not available: ', 'chatgpt-fluent-connector') . $api_model .
                        __('. The model may not be available in your region or with your API key. Please try selecting a different model in settings.', 'chatgpt-fluent-connector'));
            } elseif ($response_code === 429) {
                return new WP_Error('api_error', __('Rate limit exceeded. Please wait a moment and try again.', 'chatgpt-fluent-connector'));
            } elseif ($response_code === 403) {
                return new WP_Error('api_error', __('Access forbidden. Please check if your API key has access to this model.', 'chatgpt-fluent-connector'));
            }

            return new WP_Error('api_error', $error_message);
        }

        // Check for blocked content
        if (isset($response_body['candidates'][0]['finishReason']) &&
                $response_body['candidates'][0]['finishReason'] === 'SAFETY') {
            return new WP_Error('content_blocked', __('The content was blocked by Gemini safety filters', 'chatgpt-fluent-connector'));
        }

        // Check if we have a valid response
        if (!isset($response_body['candidates'][0]['content'])) {
            error_log('CGPTFC: Invalid Gemini response structure - missing content: ' . wp_json_encode($response_body));
            return new WP_Error('invalid_response', __('Invalid response structure from Gemini API - missing content', 'chatgpt-fluent-connector'));
        }

        // Handle empty response content (common with MAX_TOKENS finish reason)
        if (!isset($response_body['candidates'][0]['content']['parts']) ||
                empty($response_body['candidates'][0]['content']['parts']) ||
                !isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {

            // Check if it's because of MAX_TOKENS
            if (isset($response_body['candidates'][0]['finishReason']) &&
                    $response_body['candidates'][0]['finishReason'] === 'MAX_TOKENS') {
                error_log('CGPTFC: Gemini response truncated due to MAX_TOKENS limit');
                return new WP_Error('max_tokens_reached',
                        __('Response was truncated because it reached the maximum token limit. Try increasing the max_tokens setting or using a shorter prompt.', 'chatgpt-fluent-connector'));
            }

            // Check for other finish reasons
            if (isset($response_body['candidates'][0]['finishReason'])) {
                $finish_reason = $response_body['candidates'][0]['finishReason'];
                error_log('CGPTFC: Gemini response finished with reason: ' . $finish_reason);
            }

            error_log('CGPTFC: Invalid Gemini response structure - missing text: ' . wp_json_encode($response_body));
            return new WP_Error('invalid_response', __('Invalid response structure from Gemini API - no text content', 'chatgpt-fluent-connector'));
        }

        // Fix encoding in the response content
        if (isset($response_body['candidates'][0]['content']['parts'][0]['text'])) {
            $response_body['candidates'][0]['content']['parts'][0]['text'] = $this->fix_encoding($response_body['candidates'][0]['content']['parts'][0]['text']);
        }

        return $response_body;
    }

    // Add getter methods:
    public function get_last_request_json() {
        return $this->last_request_json;
    }

    public function get_last_response_json() {
        return $this->last_response_json;
    }

    /**
     * Convert OpenAI-style messages to Gemini format
     *
     * @param array $messages OpenAI-style messages
     * @return array Gemini-formatted contents
     */
    private function convert_messages_to_gemini_format($messages) {
        $gemini_contents = array();
        $system_context = '';

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // Gemini doesn't have a system role, so we prepend it to the first user message
                $system_context .= $message['content'] . "\n\n";
            } else {
                $role = ($message['role'] === 'user') ? 'user' : 'model';

                // If this is the first user message and we have system context, prepend it
                if ($role === 'user' && !empty($system_context) && empty($gemini_contents)) {
                    $content = $system_context . $message['content'];
                    $system_context = ''; // Clear it so we don't add it again
                } else {
                    $content = $message['content'];
                }

                $gemini_contents[] = array(
                    'role' => $role,
                    'parts' => array(
                        array('text' => $content)
                    )
                );
            }
        }

        // If we only had a system message, create a user message with it
        if (!empty($system_context) && empty($gemini_contents)) {
            $gemini_contents[] = array(
                'role' => 'user',
                'parts' => array(
                    array('text' => $system_context)
                )
            );
        }

        return $gemini_contents;
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

        // Check if response has content but no text (empty response)
        if (isset($response['candidates'][0]['content']) &&
                (!isset($response['candidates'][0]['content']['parts']) ||
                empty($response['candidates'][0]['content']['parts']))) {

            // Check the finish reason
            if (isset($response['candidates'][0]['finishReason'])) {
                $finish_reason = $response['candidates'][0]['finishReason'];
                if ($finish_reason === 'MAX_TOKENS') {
                    return new WP_Error('max_tokens', __('Response truncated: Maximum token limit reached before any content was generated. Try increasing max_tokens.', 'chatgpt-fluent-connector'));
                } elseif ($finish_reason === 'SAFETY') {
                    return new WP_Error('safety_filter', __('Response blocked by Gemini safety filters', 'chatgpt-fluent-connector'));
                }
            }

            return new WP_Error('empty_response', __('Gemini returned an empty response', 'chatgpt-fluent-connector'));
        }

        if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Gemini API - no text content', 'chatgpt-fluent-connector'));
        }

        // The content should already be fixed in make_request, but ensure it's clean
        $content = $response['candidates'][0]['content']['parts'][0]['text'];
        return $this->fix_encoding($content);
    }

    /**
     * Process a form submission with a prompt - FIXED with proper chunking support
     *
     * @param int $prompt_id The prompt post ID
     * @param array $form_data The form submission data
     * @param int $entry_id The entry ID (optional)
     * @return string|WP_Error The response content or error
     */
    public function process_form_with_prompt($prompt_id, $form_data, $entry_id = null) {
        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_sfaic_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_sfaic_user_prompt_template', true);
        $temperature = get_post_meta($prompt_id, '_sfaic_temperature', true);
        $max_tokens = get_post_meta($prompt_id, '_sfaic_max_tokens', true);
        $prompt_type = get_post_meta($prompt_id, '_sfaic_prompt_type', true);
        $enable_chunking = get_post_meta($prompt_id, '_sfaic_enable_chunking', true);
        $model = get_option('sfaic_gemini_model', 'gemini-1.5-pro-latest');

        // FIXED: Check if chunking is enabled and needed
        if ($enable_chunking === '1' && intval($max_tokens) > 8192) {
            error_log('SFAIC Gemini: Using chunked processing for ' . intval($max_tokens) . ' tokens');
            return $this->process_form_with_prompt_chunked($prompt_id, $form_data, $entry_id);
        }

        // Set default prompt type if not set
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        // Gemini has a max output token limit of 8192
        if (intval($max_tokens) > 8192) {
            $max_tokens = 8192;
        }

        // Prepare the user prompt based on prompt type
        $user_prompt = '';
        if ($prompt_type === 'all_form_data') {
            // Use all form data
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
        $user_prompt = apply_filters('sfaic_process_form_with_prompt', $user_prompt, $prompt_id, $form_data);

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

        // Store the complete prompt for the entry
        if (!empty($entry_id)) {
            $complete_prompt_string = '';
            if (!empty($system_prompt)) {
                $complete_prompt_string .= "System: " . $system_prompt . "\n\n";
            }
            $complete_prompt_string .= "User: " . $user_prompt;

            update_post_meta($entry_id, '_gemini_complete_prompt', $complete_prompt_string);
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

        // Store token usage for the entry
        if (!empty($entry_id)) {
            $token_usage = $this->get_last_token_usage();
            if (!empty($token_usage)) {
                update_post_meta($entry_id, '_gemini_token_usage', $token_usage);
            }
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
        $form_id = get_post_meta($prompt_id, '_sfaic_fluent_form_id', true);

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

    /**
     * Process a form submission with chunked responses for long content - FIXED IMPLEMENTATION
     */
    public function process_form_with_prompt_chunked($prompt_id, $form_data, $entry_id = null) {
        error_log('SFAIC Gemini: Starting chunked processing');
        
        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_sfaic_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_sfaic_user_prompt_template', true);
        $temperature = get_post_meta($prompt_id, '_sfaic_temperature', true);
        $max_tokens = get_post_meta($prompt_id, '_sfaic_max_tokens', true);
        $prompt_type = get_post_meta($prompt_id, '_sfaic_prompt_type', true);
        $model = get_option('sfaic_gemini_model', 'gemini-1.5-pro-latest');

        // Prepare the initial user prompt
        $user_prompt = $this->prepare_user_prompt($prompt_id, $form_data, $prompt_type, $user_prompt_template);

        if (is_wp_error($user_prompt)) {
            return $user_prompt;
        }

        // Enhanced system prompt for chunking
        $chunked_system_prompt = $system_prompt . "\n\n" .
                "IMPORTANT: You are generating a comprehensive, detailed response. " .
                "Write naturally and in detail. If you reach your output limit, stop at a complete thought or sentence. " .
                "Do not add any continuation markers, \"(continued)\" text, or special indicators. " .
                "Just write your content naturally and stop when you reach the limit.";

        // Calculate target and chunk parameters
        $target_tokens = intval($max_tokens);
        $max_chunks = min(ceil($target_tokens / 6000), 40); // Reasonable chunk limit
        $total_tokens_used = 0;
        $full_response = '';
        $conversation = array();

        // Initialize conversation
        if (!empty($chunked_system_prompt)) {
            $conversation[] = array('role' => 'system', 'content' => $chunked_system_prompt);
        }
        $conversation[] = array('role' => 'user', 'content' => $user_prompt);

        error_log("SFAIC Gemini: Starting chunked generation - target: {$target_tokens} tokens, max chunks: {$max_chunks}");

        for ($chunk_num = 0; $chunk_num < $max_chunks; $chunk_num++) {
            // Calculate chunk size based on model and remaining tokens
            $remaining_tokens = $target_tokens - $total_tokens_used;
            $chunk_tokens = $this->calculate_chunk_size($model, $remaining_tokens, $chunk_num);

            if ($chunk_tokens < 100) {
                error_log("SFAIC Gemini: Stopping - chunk size too small: {$chunk_tokens}");
                break;
            }

            error_log("SFAIC Gemini: Generating chunk " . ($chunk_num + 1) . " with {$chunk_tokens} tokens");

            // Make API request
            $response = $this->make_request($conversation, $model, $chunk_tokens, floatval($temperature));

            if (is_wp_error($response)) {
                if ($chunk_num === 0) {
                    error_log("SFAIC Gemini: First chunk failed: " . $response->get_error_message());
                    return $response;
                }
                error_log("SFAIC Gemini: Chunk {$chunk_num} failed, stopping with partial response");
                break;
            }

            $chunk_content = $this->get_response_content($response);
            if (is_wp_error($chunk_content)) {
                if ($chunk_num === 0) {
                    return $chunk_content;
                }
                break;
            }

            // Track token usage
            $token_usage = $this->get_last_token_usage();
            $chunk_tokens_used = isset($token_usage['completion_tokens']) ? $token_usage['completion_tokens'] : 0;
            $total_tokens_used += $chunk_tokens_used;

            error_log("SFAIC Gemini: Chunk " . ($chunk_num + 1) . " generated {$chunk_tokens_used} tokens. Total: {$total_tokens_used}");

            // Add chunk to full response
            $full_response .= $chunk_content;

            // Check stopping conditions
            if ($total_tokens_used >= $target_tokens * 0.95) {
                error_log("SFAIC Gemini: Reached target token limit ({$total_tokens_used}/{$target_tokens})");
                break;
            }

            if ($this->is_response_complete($chunk_content, $chunk_num)) {
                error_log("SFAIC Gemini: Response appears complete after chunk " . ($chunk_num + 1));
                break;
            }

            if ($chunk_tokens_used < $chunk_tokens * 0.5) {
                error_log("SFAIC Gemini: Chunk significantly shorter than requested, likely complete");
                break;
            }

            // Manage conversation length to prevent context overflow
            if (count($conversation) > 8) {
                // Keep system prompt, original request, and last 4 exchanges
                $conversation = array_merge(
                    array_slice($conversation, 0, 2), // System + original user message
                    array_slice($conversation, -4)    // Last 4 messages
                );
            }

            // Add response and continuation prompt
            $conversation[] = array('role' => 'model', 'content' => $chunk_content); // Note: Gemini uses 'model' not 'assistant'
            
            $continuation_prompt = $this->generate_continuation_prompt($chunk_num, $chunk_content, strlen($full_response));
            $conversation[] = array('role' => 'user', 'content' => $continuation_prompt);

            error_log("SFAIC Gemini: Added continuation prompt: " . substr($continuation_prompt, 0, 100) . "...");
        }

        // Store chunking metadata
        if (!empty($entry_id)) {
            update_post_meta($entry_id, '_gemini_chunked_response', true);
            update_post_meta($entry_id, '_gemini_chunks_count', $chunk_num + 1);
            update_post_meta($entry_id, '_gemini_total_tokens_generated', $total_tokens_used);
            update_post_meta($entry_id, '_gemini_response_length', strlen($full_response));
        }

        error_log("SFAIC Gemini: Chunked generation complete. " . ($chunk_num + 1) . " chunks, {$total_tokens_used} tokens, " . strlen($full_response) . " characters");

        return $full_response;
    }

    /**
     * Calculate appropriate chunk size based on model, remaining tokens, and chunk number
     */
    private function calculate_chunk_size($model, $remaining_tokens, $chunk_num) {
        // Base chunk sizes for different Gemini models
        $base_chunk_sizes = array(
            'gemini-1.5-pro-latest' => 7000,
            'gemini-1.5-flash-latest' => 7000,
            'gemini-2.0-flash-exp' => 7000,
            'gemini-2.5-flash' => 7500,
            'gemini-2.5-pro' => 7500,
            'gemini-exp-1219' => 7000,
        );

        $base_chunk = isset($base_chunk_sizes[$model]) ? $base_chunk_sizes[$model] : 7000;
        
        // Reduce chunk size slightly for later chunks to maintain quality
        if ($chunk_num > 5) {
            $base_chunk = intval($base_chunk * 0.8);
        }
        
        // Don't exceed remaining tokens or Gemini's max output limit
        return min($base_chunk, $remaining_tokens, 8192);
    }

    /**
     * Check if response seems complete
     */
    private function is_response_complete($content, $chunk_num) {
        // Don't check completion on first chunk
        if ($chunk_num === 0) return false;

        // Very short responses might indicate completion
        if (strlen(trim($content)) < 150) return true;

        // Check for natural ending patterns
        $ending_patterns = array(
            '/\.\s*$/',                               // Ends with period
            '/<\/(p|div|section|article|conclusion)>\s*$/i', // Ends with closing HTML tag
            '/\n\n\s*$/',                            // Ends with double newline
            '/[Cc]onclusion.*[.!]\s*$/m',           // Contains conclusion
            '/[Tt]hank you.*[.!]\s*$/m',            // Thank you ending
            '/[Hh]ope this helps.*[.!]\s*$/m',      // Common closing phrase
            '/[Bb]est regards.*[.!]\s*$/m',         // Email-style closing
            '/[Ii]n summary.*[.!]\s*$/m',           // Summary ending
            '/[Tt]o conclude.*[.!]\s*$/m',          // Conclusion phrase
        );

        foreach ($ending_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate smart continuation prompts optimized for Gemini
     */
    private function generate_continuation_prompt($chunk_num, $last_chunk, $total_length) {
        // Check if last chunk ended mid-sentence
        $last_chunk_trimmed = trim($last_chunk);
        $ends_mid_sentence = !preg_match('/[.!?]\s*$/', $last_chunk_trimmed);
        
        if ($ends_mid_sentence) {
            return "Please complete the current sentence and then continue with the rest of your detailed response.";
        }

        // Vary continuation prompts to maintain natural flow
        $prompts = array(
            "Please continue with the next part of your comprehensive response.",
            "Continue providing more detailed information and analysis.",
            "Please proceed with additional insights and elaboration.",
            "Continue developing your response with more specific details.",
            "Please add more comprehensive information to your analysis.",
            "Continue with the next section of your detailed explanation.",
            "Please provide further elaboration and examples on this topic.",
            "Continue expanding on this subject with additional details."
        );

        return $prompts[$chunk_num % count($prompts)];
    }

    /**
     * Prepare user prompt helper method
     */
    private function prepare_user_prompt($prompt_id, $form_data, $prompt_type, $user_prompt_template) {
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        $user_prompt = '';
        if ($prompt_type === 'all_form_data') {
            $user_prompt = $this->format_all_form_data($form_data, $prompt_id);
        } else {
            if (empty($user_prompt_template)) {
                return new WP_Error('no_prompt_template', __('No user prompt template configured', 'chatgpt-fluent-connector'));
            }

            $user_prompt = $user_prompt_template;

            foreach ($form_data as $field_key => $field_value) {
                if (!is_scalar($field_key)) {
                    continue;
                }

                if (is_array($field_value)) {
                    $field_value = implode(', ', $field_value);
                } elseif (!is_scalar($field_value)) {
                    continue;
                }

                $user_prompt = str_replace('{' . $field_key . '}', $field_value, $user_prompt);
            }

            $user_prompt = preg_replace('/\{[^}]+\}/', '', $user_prompt);
        }

        if (empty($user_prompt)) {
            return new WP_Error('empty_prompt', __('User prompt is empty after processing', 'chatgpt-fluent-connector'));
        }

        // Apply HTML template filter
        $user_prompt = apply_filters('sfaic_process_form_with_prompt', $user_prompt, $prompt_id, $form_data);

        return $user_prompt;
    }
}