<?php

/**
 * Anthropic Claude API Class - FIXED with Working Chunking Support
 * 
 * Handles API requests to the Anthropic Claude API and tracks token usage
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class SFAIC_Claude_API {

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
     * Get token usage from last API call
     */
    public function get_last_token_usage() {
        if ($this->last_response && isset($this->last_response['usage'])) {
            return array(
                'prompt_tokens' => $this->last_response['usage']['input_tokens'] ?? 0,
                'completion_tokens' => $this->last_response['usage']['output_tokens'] ?? 0,
                'total_tokens' => ($this->last_response['usage']['input_tokens'] ?? 0) + ($this->last_response['usage']['output_tokens'] ?? 0)
            );
        }
        return array();
    }

    /**
     * Make a request to the Claude API
     *
     * @param array $messages Array of message objects (role, content)
     * @param string $model Optional. The model to use. If null, uses the setting.
     * @param int $max_tokens Optional. Maximum tokens in the response.
     * @param float $temperature Optional. Temperature for response randomness.
     * @return array|WP_Error Response from API or error
     */
    public function make_request($messages, $model = null, $max_tokens = 1000, $temperature = 0.7) {
        $api_key = get_option('sfaic_claude_api_key');

        if (empty($api_key)) {
            return new WP_Error('no_api_key', __('Claude API key is not set', 'chatgpt-fluent-connector'));
        }

        // Use specified model or fall back to settings
        if ($model === null) {
            $model = get_option('sfaic_claude_model', 'claude-opus-4-20250514');
        }

        // Token limits for Claude models (output limits)
        $token_limits = [
            'claude-opus-4-20250514' => 4096,
            'claude-sonnet-4-20250514' => 4096,
            'claude-3-opus-20240229' => 4096,
            'claude-3-sonnet-20240229' => 4096,
            'claude-3-haiku-20240307' => 4096,
            'claude-2.1' => 4096,
            'claude-2.0' => 4096,
            'claude-instant-1.2' => 4096
        ];

        // Set default max token limit for Claude (output limit)
        $model_limit = isset($token_limits[$model]) ? $token_limits[$model] : 4096;

        // Ensure max_tokens is within output limits
        $max_tokens = min(intval($max_tokens), $model_limit);

        // Build the API endpoint
        $api_endpoint = 'https://api.anthropic.com/v1/messages';

        // Convert messages to Claude format
        $claude_messages = $this->convert_messages_to_claude_format($messages);

        $headers = array(
            'Content-Type' => 'application/json',
            'x-api-key' => $api_key,
            'anthropic-version' => '2023-06-01'
        );

        $body = array(
            'model' => $model,
            'messages' => $claude_messages['messages'],
            'max_tokens' => intval($max_tokens),
            'temperature' => floatval($temperature)
        );

        // Add system message if present BEFORE storing JSON
        if (!empty($claude_messages['system'])) {
            $body['system'] = $claude_messages['system'];
        }

        // Store the complete request JSON
        $this->last_request_json = wp_json_encode($body, JSON_PRETTY_PRINT);

        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'method' => 'POST',
            'data_format' => 'body',
            'timeout' => 1000
        );

        // Make the API request
        $response = wp_remote_post($api_endpoint, $args);

        // Check for WordPress request errors
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log('CGPTFC: Claude API WordPress Request Error: ' . $error_message);
            return $response;
        }

        // Get response code and body
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body_raw = wp_remote_retrieve_body($response);
        $response_body = json_decode($response_body_raw, true);

        // Store the response for token tracking
        $this->last_response = $response_body;
        $this->last_response_json = $response_body_raw;

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
            error_log('CGPTFC: Claude API Error: ' . $error_message);

            return new WP_Error('api_error', $error_message);
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
     * Convert OpenAI-style messages to Claude format
     *
     * @param array $messages OpenAI-style messages
     * @return array Claude-formatted messages with separate system message
     */
    private function convert_messages_to_claude_format($messages) {
        $claude_messages = array();
        $system_message = '';

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                // Claude handles system messages separately
                $system_message .= $message['content'] . "\n\n";
            } else {
                // Claude uses "user" and "assistant" roles
                $role = ($message['role'] === 'user') ? 'user' : 'assistant';

                $claude_messages[] = array(
                    'role' => $role,
                    'content' => $message['content']
                );
            }
        }

        return array(
            'messages' => $claude_messages,
            'system' => trim($system_message)
        );
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

        if (!isset($response['content'][0]['text'])) {
            return new WP_Error('invalid_response', __('Invalid response from Claude API', 'chatgpt-fluent-connector'));
        }

        return $response['content'][0]['text'];
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
        $model = get_option('sfaic_claude_model', 'claude-opus-4-20250514');

        // FIXED: Check if chunking is enabled and needed
        if ($enable_chunking === '1' && intval($max_tokens) > 4096) {
            error_log('SFAIC Claude: Using chunked processing for ' . intval($max_tokens) . ' tokens');
            return $this->process_form_with_prompt_chunked($prompt_id, $form_data, $entry_id);
        }

        // Set default prompt type if not set
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        // Claude has a max output token limit of 4096
        if (intval($max_tokens) > 4096) {
            $max_tokens = 4096;
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

        // Tell Claude it can use HTML in responses
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

            update_post_meta($entry_id, '_claude_complete_prompt', $complete_prompt_string);
        }

        // Make the API request
        $response = $this->make_request(
                $messages,
                $model,
                !empty($max_tokens) ? intval($max_tokens) : 1000,
                !empty($temperature) ? floatval($temperature) : 0.7
        );

        if (is_wp_error($response)) {
            error_log('CGPTFC: Error in Claude API response: ' . $response->get_error_message());
            return $response;
        }

        $content = $this->get_response_content($response);

        // Store token usage for the entry
        if (!empty($entry_id)) {
            $token_usage = $this->get_last_token_usage();
            if (!empty($token_usage)) {
                update_post_meta($entry_id, '_claude_token_usage', $token_usage);
            }
        }

        return $content;
    }

    /**
     * Format all form data into a structured text for Claude
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
        error_log('SFAIC Claude: Starting chunked processing');
        
        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_sfaic_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_sfaic_user_prompt_template', true);
        $temperature = get_post_meta($prompt_id, '_sfaic_temperature', true);
        $max_tokens = get_post_meta($prompt_id, '_sfaic_max_tokens', true);
        $prompt_type = get_post_meta($prompt_id, '_sfaic_prompt_type', true);
        $model = get_option('sfaic_claude_model', 'claude-opus-4-20250514');

        // Prepare the initial user prompt
        $user_prompt = $this->prepare_user_prompt($prompt_id, $form_data, $prompt_type, $user_prompt_template);

        if (is_wp_error($user_prompt)) {
            return $user_prompt;
        }

        // Enhanced system prompt for chunking - optimized for Claude
        $chunked_system_prompt = $system_prompt . "\n\n" .
                "IMPORTANT: You are generating a comprehensive, detailed response. " .
                "Write naturally and in great detail. If you reach your output limit, stop at a complete thought or sentence. " .
                "Do not add any continuation markers, \"(continued)\" text, or special indicators. " .
                "Just write your content naturally and stop when you reach the limit. " .
                "Continue naturally from where you left off if the conversation continues.";

        // Calculate target and chunk parameters
        $target_tokens = intval($max_tokens);
        $max_chunks = min(ceil($target_tokens / 3500), 40); // Reasonable chunk limit for Claude
        $total_tokens_used = 0;
        $full_response = '';
        $conversation = array();

        // Initialize conversation
        if (!empty($chunked_system_prompt)) {
            $conversation[] = array('role' => 'system', 'content' => $chunked_system_prompt);
        }
        $conversation[] = array('role' => 'user', 'content' => $user_prompt);

        error_log("SFAIC Claude: Starting chunked generation - target: {$target_tokens} tokens, max chunks: {$max_chunks}");

        for ($chunk_num = 0; $chunk_num < $max_chunks; $chunk_num++) {
            // Calculate chunk size based on model and remaining tokens
            $remaining_tokens = $target_tokens - $total_tokens_used;
            $chunk_tokens = $this->calculate_chunk_size($model, $remaining_tokens, $chunk_num);

            if ($chunk_tokens < 100) {
                error_log("SFAIC Claude: Stopping - chunk size too small: {$chunk_tokens}");
                break;
            }

            error_log("SFAIC Claude: Generating chunk " . ($chunk_num + 1) . " with {$chunk_tokens} tokens");

            // Make API request
            $response = $this->make_request($conversation, $model, $chunk_tokens, floatval($temperature));

            if (is_wp_error($response)) {
                if ($chunk_num === 0) {
                    error_log("SFAIC Claude: First chunk failed: " . $response->get_error_message());
                    return $response;
                }
                error_log("SFAIC Claude: Chunk {$chunk_num} failed, stopping with partial response");
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

            error_log("SFAIC Claude: Chunk " . ($chunk_num + 1) . " generated {$chunk_tokens_used} tokens. Total: {$total_tokens_used}");

            // Add chunk to full response
            $full_response .= $chunk_content;

            // Check stopping conditions
            if ($total_tokens_used >= $target_tokens * 0.95) {
                error_log("SFAIC Claude: Reached target token limit ({$total_tokens_used}/{$target_tokens})");
                break;
            }

            if ($this->is_response_complete($chunk_content, $chunk_num)) {
                error_log("SFAIC Claude: Response appears complete after chunk " . ($chunk_num + 1));
                break;
            }

            if ($chunk_tokens_used < $chunk_tokens * 0.5) {
                error_log("SFAIC Claude: Chunk significantly shorter than requested, likely complete");
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
            $conversation[] = array('role' => 'assistant', 'content' => $chunk_content);
            
            $continuation_prompt = $this->generate_continuation_prompt($chunk_num, $chunk_content, strlen($full_response));
            $conversation[] = array('role' => 'user', 'content' => $continuation_prompt);

            error_log("SFAIC Claude: Added continuation prompt: " . substr($continuation_prompt, 0, 100) . "...");
        }

        // Store chunking metadata
        if (!empty($entry_id)) {
            update_post_meta($entry_id, '_claude_chunked_response', true);
            update_post_meta($entry_id, '_claude_chunks_count', $chunk_num + 1);
            update_post_meta($entry_id, '_claude_total_tokens_generated', $total_tokens_used);
            update_post_meta($entry_id, '_claude_response_length', strlen($full_response));
        }

        error_log("SFAIC Claude: Chunked generation complete. " . ($chunk_num + 1) . " chunks, {$total_tokens_used} tokens, " . strlen($full_response) . " characters");

        return $full_response;
    }

    /**
     * Calculate appropriate chunk size based on model, remaining tokens, and chunk number
     */
    private function calculate_chunk_size($model, $remaining_tokens, $chunk_num) {
        // Base chunk sizes for different Claude models (conservative for stability)
        $base_chunk_sizes = array(
            'claude-opus-4-20250514' => 3500,
            'claude-sonnet-4-20250514' => 3500,
            'claude-3-opus-20240229' => 3500,
            'claude-3-sonnet-20240229' => 3500,
            'claude-3-haiku-20240307' => 3500,
            'claude-2.1' => 3500,
            'claude-2.0' => 3500,
            'claude-instant-1.2' => 3500
        );

        $base_chunk = isset($base_chunk_sizes[$model]) ? $base_chunk_sizes[$model] : 3500;
        
        // Reduce chunk size slightly for later chunks to maintain quality
        if ($chunk_num > 5) {
            $base_chunk = intval($base_chunk * 0.8);
        }
        
        // Don't exceed remaining tokens or Claude's max output limit
        return min($base_chunk, $remaining_tokens, 4096);
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
            '/[Ss]incerely.*[.!]\s*$/m',            // Email closing
        );

        foreach ($ending_patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate smart continuation prompts optimized for Claude
     */
    private function generate_continuation_prompt($chunk_num, $last_chunk, $total_length) {
        // Check if last chunk ended mid-sentence
        $last_chunk_trimmed = trim($last_chunk);
        $ends_mid_sentence = !preg_match('/[.!?]\s*$/', $last_chunk_trimmed);
        
        if ($ends_mid_sentence) {
            return "Please complete the current thought and then continue with the rest of the content.";
        }

        // Claude-specific continuation prompts that work well
        $prompts = array(
            "Please continue with the next section of your comprehensive response.",
            "Continue providing more detailed information on this topic.",
            "Please provide the next part of your analysis.",
            "Continue with additional insights and information.",
            "Please proceed with further elaboration on this subject.",
            "Continue developing your response with more details.",
            "Please add more comprehensive information to your response.",
            "Continue with the next part of your detailed explanation."
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