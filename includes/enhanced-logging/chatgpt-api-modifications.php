<?php
/**
 * Modified ChatGPT API Class with Enhanced Logging
 * 
 * These modifications should be added to the class-chatgpt-custom-api.php file
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process a form submission with a prompt - Enhanced with detailed logging
 *
 * @param int $prompt_id The prompt post ID
 * @param array $form_data The form submission data
 * @param int $entry_id The entry ID (optional, will be determined from form data if not provided)
 * @return string|WP_Error The response content or error
 */
function cgptfc_enhanced_process_form_with_prompt($prompt_id, $form_data, $entry_id = null) {
    $debug_mode = get_option('cgptfc_debug_mode', '0');
    
    // If entry_id isn't provided, try to get it from form data
    if ($entry_id === null && isset($form_data['_fluentform_id'])) {
        $entry_id = isset($form_data['entry_id']) ? $form_data['entry_id'] : 0;
    }
    
    // Allow other plugins to hook before processing
    $timer_data = apply_filters('cgptfc_before_process_form_with_prompt', array(), $prompt_id, $form_data, $entry_id);
    
    if ($debug_mode === '1') {
        error_log('CGPTFC: Processing prompt ID: ' . $prompt_id);
    }

    // Get prompt settings
    $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
    $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
    $temperature = get_post_meta($prompt_id, '_cgptfc_temperature', true);
    $max_tokens = get_post_meta($prompt_id, '_cgptfc_max_tokens', true);
    $prompt_type = get_post_meta($prompt_id, '_cgptfc_prompt_type', true);
    $model = get_option('cgptfc_model', 'gpt-3.5-turbo');

    // Set default prompt type if not set
    if (empty($prompt_type)) {
        $prompt_type = 'template';
    }

    // Check max tokens based on model
    $token_limits = [
        'gpt-3.5-turbo' => 4000,
        'gpt-4' => 8000,
        'gpt-4-turbo' => 4096,
        'gpt-4-turbo-preview' => 4096,
        'gpt-4-1106-preview' => 4096,
        'gpt-4-0613' => 8000,
        'gpt-4-0125-preview' => 4096
    ];

    // Set default max token limit
    $default_limit = 4000;
    $model_limit = isset($token_limits[$model]) ? $token_limits[$model] : $default_limit;

    // Ensure max_tokens is within model limits
    if (intval($max_tokens) > $model_limit) {
        $max_tokens = $model_limit;
        if ($debug_mode === '1') {
            error_log('CGPTFC: Capped max_tokens to model limit: ' . $model_limit);
        }
    }

    // Prepare the user prompt based on prompt type
    $user_prompt = '';
    if ($prompt_type === 'all_form_data') {
        // Use all form data
        $user_prompt = format_all_form_data($form_data, $prompt_id);
    } else {
        // Use custom template
        if (empty($user_prompt_template)) {
            $error = new WP_Error('no_prompt_template', __('No user prompt template configured', 'chatgpt-fluent-connector'));
            
            // Log error if logging is enabled
            do_action('cgptfc_after_process_form_with_prompt', $error, $prompt_id, $form_data, $entry_id, $timer_data);
            
            return $error;
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
        $error = new WP_Error('empty_prompt', __('User prompt is empty after processing', 'chatgpt-fluent-connector'));
        
        // Log error if logging is enabled
        do_action('cgptfc_after_process_form_with_prompt', $error, $prompt_id, $form_data, $entry_id, $timer_data);
        
        return $error;
    }

    // Tell ChatGPT it can use HTML in responses
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
        error_log('CGPTFC: Sending prompt with ' . count($messages) . ' messages');
        error_log('CGPTFC: User prompt: ' . substr($user_prompt, 0, 200) . '...');
    }

    // Make the API request
    $response = make_request(
        $messages,
        $model,
        !empty($max_tokens) ? intval($max_tokens) : 1000,
        !empty($temperature) ? floatval($temperature) : 0.7
    );

    if (is_wp_error($response)) {
        error_log('CGPTFC: Error in API response: ' . $response->get_error_message());
        
        // Log error if logging is enabled
        do_action('cgptfc_after_process_form_with_prompt', $response, $prompt_id, $form_data, $entry_id, $timer_data);
        
        return $response;
    }

    $content = get_response_content($response);
    
    if ($debug_mode === '1') {
        error_log('CGPTFC: Got response of length: ' . strlen($content));
    }
    
    // Log successful response if logging is enabled
    do_action('cgptfc_after_process_form_with_prompt', $content, $prompt_id, $form_data, $entry_id, $timer_data);

    return $content;
}

/**
 * Format all form data for enhanced tracking
 * 
 * @param array $form_data The form data
 * @param int $prompt_id The prompt ID
 * @return string Formatted form data text
 */
function format_all_form_data($form_data, $prompt_id) {
    $output = __('Here is the submitted form data:', 'chatgpt-fluent-connector') . "\n\n";

    // Get field labels if possible
    $field_labels = get_form_field_labels($prompt_id);

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
 * Get form field labels
 * 
 * @param int $prompt_id The prompt ID
 * @return array Field labels
 */
function get_form_field_labels($prompt_id) {
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