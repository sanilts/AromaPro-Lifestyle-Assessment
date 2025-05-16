<?php

/**
 * ChatGPT Fluent Forms Integration Class
 * 
 * Handles integration with Fluent Forms
 */
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Fluent_Integration {

    /**
     * Constructor
     */
    public function __construct() {
        // Hook into Fluent Forms submission
        add_action('fluentform/submission_inserted', array($this, 'handle_form_submission'), 20, 3);

        // Use the correct filter with the proper parameters
        add_filter('fluentform/submission_confirmation', array($this, 'modify_confirmation_message'), 10, 5);

        // Initialize async hooks
        $this->initialize_async_hooks();
    }

    /**
     * Handle form submission with asynchronous processing
     * 
     * @param int $entry_id The submission entry ID
     * @param array $form_data The submitted form data
     * @param object $form The form object
     */
    public function handle_form_submission($entry_id, $form_data, $form) {
        $form_id = $form->id;
        $debug_mode = get_option('cgptfc_debug_mode', '0');

        if ($debug_mode === '1') {
            error_log('CGPTFC: handle_form_submission called for form ' . $form_id . ', entry ' . $entry_id);
        }

        // Find prompts configured for this form
        $args = array(
            'post_type' => 'cgptfc_prompt',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_cgptfc_fluent_form_id',
                    'value' => $form_id,
                    'compare' => '='
                )
            )
        );

        $prompts = get_posts($args);

        if (empty($prompts)) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: No prompts found for form ' . $form_id);
            }
            return;
        }

        if ($debug_mode === '1') {
            error_log('CGPTFC: Found ' . count($prompts) . ' prompts for form ' . $form_id);
        }

        // Process each prompt asynchronously
        foreach ($prompts as $prompt) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: Scheduling async processing for prompt: ' . $prompt->ID . ' - ' . $prompt->post_title);
            }

            // Schedule the async processing using WordPress cron
            wp_schedule_single_event(
                    time(),
                    'cgptfc_process_form_async',
                    array(
                        'prompt_id' => $prompt->ID,
                        'form_data' => $form_data,
                        'entry_id' => $entry_id,
                        'form_id' => $form_id
                    )
            );
        }

        // Trigger the cron event immediately
        if (function_exists('spawn_cron')) {
            spawn_cron();
        }
    }

    /**
     * Process a form submission asynchronously
     * 
     * @param int $prompt_id The prompt ID
     * @param array $form_data The form data
     * @param int $entry_id The entry ID
     * @param int $form_id The form ID
     */
    public function process_form_async($prompt_id, $form_data, $entry_id, $form_id) {
        $debug_mode = get_option('cgptfc_debug_mode', '0');

        if ($debug_mode === '1') {
            error_log('CGPTFC: Running async process for prompt_id ' . $prompt_id . ', entry_id ' . $entry_id);
        }

        // Get the form object
        if (function_exists('wpFluent')) {
            $form = wpFluent()->table('fluentform_forms')
                    ->where('id', $form_id)
                    ->first();

            if ($form) {
                // Process the prompt normally
                $this->process_prompt($prompt_id, $form_data, $entry_id, $form);
            } else {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Form not found for ID: ' . $form_id);
                }
            }
        } else {
            if ($debug_mode === '1') {
                error_log('CGPTFC: wpFluent function not available');
            }
        }
    }

    /**
     * Hook initialization function - Add this to your constructor
     */
    public function initialize_async_hooks() {
        // Register the cron event
        add_action('cgptfc_process_form_async', array($this, 'process_form_async'), 10, 4);
    }

    /**
     * Modify the confirmation message to include AI response
     * 
     * @param array $returnData The return data array
     * @param object $form The form object
     * @param array $confirmation The confirmation settings
     * @param int $insertId The submission ID
     * @param array $formData The form data
     * @return array Modified return data
     */
    public function modify_confirmation_message($returnData, $form, $confirmation, $insertId, $formData) {
        $debug_mode = get_option('cgptfc_debug_mode', '0');

        if ($debug_mode === '1') {
            error_log('CGPTFC: modify_confirmation_message called');
            error_log('CGPTFC: Form ID: ' . $form->id);
            error_log('CGPTFC: Entry ID: ' . $insertId);
            error_log('CGPTFC: returnData: ' . print_r($returnData, true));
        }

        // Check if we should show the response for this form
        $args = array(
            'post_type' => 'cgptfc_prompt',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'AND',
                array(
                    'key' => '_cgptfc_fluent_form_id',
                    'value' => $form->id,
                    'compare' => '='
                ),
                array(
                    'key' => '_cgptfc_show_to_user',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );

        $show_prompts = get_posts($args);

        if ($debug_mode === '1') {
            error_log('CGPTFC: Found ' . count($show_prompts) . ' prompts configured to show response');
        }

        if (!empty($show_prompts)) {
            // Get the AI response from the database
            $ai_response_html = $this->get_ai_response_html($form->id, $insertId);

            if (!empty($ai_response_html)) {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Adding AI response to confirmation message');
                }

                // Modify the message based on the return type
                if (isset($returnData['message'])) {
                    $returnData['message'] .= $ai_response_html;
                } elseif (isset($returnData['messageToShow'])) {
                    $returnData['messageToShow'] .= $ai_response_html;
                }
            } else {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: No AI response found for entry ID: ' . $insertId);
                }
            }
        }

        return $returnData;
    }

    /**
     * Get the AI response HTML - Modified to properly render HTML and show correct provider
     * 
     * @param int $form_id The form ID
     * @param int $entry_id The entry ID
     * @return string The HTML to display
     */
    private function get_ai_response_html($form_id, $entry_id) {
        $debug_mode = get_option('cgptfc_debug_mode', '0');

        if ($debug_mode === '1') {
            error_log('CGPTFC: Looking for AI response for form_id: ' . $form_id . ', entry_id: ' . $entry_id);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'cgptfc_response_logs';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: Response logs table does not exist');
            }
            return '';
        }

        // Get the most recent response for this form entry, including the prompt and provider
        $result = $wpdb->get_row($wpdb->prepare(
                        "SELECT user_prompt, ai_response, provider FROM {$table_name} WHERE form_id = %d AND entry_id = %d ORDER BY created_at DESC LIMIT 1",
                        $form_id, $entry_id
        ));

        if (empty($result)) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: No response found in database');
            }

            // Wait a bit for the response to be generated
            usleep(500000); // Wait 0.5 seconds
            // Try again
            $result = $wpdb->get_row($wpdb->prepare(
                            "SELECT user_prompt, ai_response, provider FROM {$table_name} WHERE form_id = %d AND entry_id = %d ORDER BY created_at DESC LIMIT 1",
                            $form_id, $entry_id
            ));

            if (empty($result)) {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Still no response after waiting');
                }
                return '';
            }
        }

        if ($debug_mode === '1') {
            error_log('CGPTFC: Found response: ' . substr($result->ai_response, 0, 50) . '...');
            error_log('CGPTFC: Provider: ' . $result->provider);
        }

        // Get the provider name for display
        $provider = isset($result->provider) ? $result->provider : get_option('cgptfc_api_provider', 'openai');
        $provider_name = ($provider === 'gemini') ? 'Google Gemini' : 'ChatGPT';

        // Build the HTML to show both prompt and response
        $html = '<div class="cgptfc-response-wrapper">';

        // Display the response - IMPORTANT: Allow HTML to render properly by using wp_kses_post instead of esc_html
        $html .= '<div class="cgptfc-response">';
        //$html .= '<div class="cgptfc-response-header">' . sprintf(__('%s Response', 'chatgpt-fluent-connector'), $provider_name) . '</div>';
        $html .= '<div class="cgptfc-response-content">';
        $html .= wp_kses_post(nl2br($result->ai_response)); // Allow HTML but still sanitize with wp_kses_post
        $html .= '</div>';
        $html .= '</div>';

        // Format the full form data to include at the bottom
        //$html .= $this->get_formatted_form_data_html($form_id, $entry_id);

        $html .= '</div>';

        // Add enhanced styling
        $html .= '<style>
        .cgptfc-response-wrapper {
            margin: 20px 0;
        }
        .cgptfc-prompt,
        .cgptfc-response {
            margin: 15px 0;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .cgptfc-response-header {
            margin-top: 0;
            font-size: 1.3em;
            font-weight: bold;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            color: #0073aa;
        }
        .cgptfc-prompt h3 {
            color: #374151;
        }
        .cgptfc-prompt-content,
        .cgptfc-response-content {
            line-height: 1.6;
            font-size: 1em;
            padding: 10px 0;
        }
        .cgptfc-form-data {
            margin-top: 20px;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
            border-left: 5px solid #444;
        }
        .cgptfc-form-data-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 1.1em;
        }
        .cgptfc-form-data-item {
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eee;
        }
        .cgptfc-form-data-label {
            font-weight: bold;
        }
        .cgptfc-prompt-content {
            background-color: #ffffff;
            padding: 15px;
            border-radius: 3px;
            font-family: monospace;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .cgptfc-response-content {
            font-size: 1.1em;
        }
        </style>';

        return $html;
    }

    /**
     * Get formatted HTML for form data
     * 
     * @param int $form_id The form ID
     * @param int $entry_id The entry ID
     * @return string The HTML showing form data
     */
    private function get_formatted_form_data_html($form_id, $entry_id) {
        // Get form data from entry
        if (!function_exists('wpFluent')) {
            return '';
        }

        $entry = wpFluent()->table('fluentform_submissions')
                ->where('form_id', $form_id)
                ->where('id', $entry_id)
                ->first();

        if (!$entry || empty($entry->response)) {
            return '';
        }

        $form_data = json_decode($entry->response, true);
        if (empty($form_data) || !is_array($form_data)) {
            return '';
        }

        // Get field labels
        $field_labels = $this->get_form_field_labels($form_id);

        // Create the HTML
        $html = '<div class="cgptfc-form-data">';
        $html .= '<div class="cgptfc-form-data-title">' . __('Submitted Form Data', 'chatgpt-fluent-connector') . '</div>';

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

            $html .= '<div class="cgptfc-form-data-item">';
            $html .= '<span class="cgptfc-form-data-label">' . esc_html($label) . ':</span> ';
            $html .= '<span class="cgptfc-form-data-value">' . esc_html($field_value) . '</span>';
            $html .= '</div>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Process a prompt with form data - Updated to properly handle provider selection
     * 
     * @param int $prompt_id The prompt ID
     * @param array $form_data The form data
     * @param int $entry_id The entry ID
     * @param object $form The form object
     * @return void
     */
    private function process_prompt($prompt_id, $form_data, $entry_id, $form) {
        $debug_mode = get_option('cgptfc_debug_mode', '0');

        if ($debug_mode === '1') {
            error_log('CGPTFC: Inside process_prompt for prompt_id ' . $prompt_id);
        }

        // Get the active provider setting
        $provider = get_option('cgptfc_api_provider', 'openai');

        if ($debug_mode === '1') {
            error_log('CGPTFC: Using ' . $provider . ' API provider');
        }

        // Get the API instance based on the provider
        $api = cgptfc_main()->get_active_api();

        if (!$api) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: API instance not available');
            }
            return;
        }

        // Get prompt settings
        $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
        $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
        $prompt_type = get_post_meta($prompt_id, '_cgptfc_prompt_type', true);

        // Set default prompt type if not set
        if (empty($prompt_type)) {
            $prompt_type = 'template';
        }

        // Prepare the user prompt based on prompt type
        $user_prompt = '';
        if ($prompt_type === 'all_form_data') {
            // Use all form data
            $user_prompt = $this->format_all_form_data($form_data, $prompt_id);
        } else {
            // Use custom template
            if (empty($user_prompt_template)) {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: No user prompt template configured');
                }
                return;
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

        // Build the complete prompt that will be sent
        $complete_prompt = '';
        if (!empty($system_prompt)) {
            $complete_prompt .= $system_prompt . "\n";
        }
        $complete_prompt .= $user_prompt;

        // Process the form with the prompt
        $ai_response_raw = $api->process_form_with_prompt($prompt_id, $form_data);
        $ai_response = $this->clean_html_response($ai_response_raw);

        $ai_response = str_replace('<br>', '', $ai_response);

        // Check if we got a valid response or an error
        if (is_wp_error($ai_response)) {
            if ($debug_mode === '1') {
                error_log('CGPTFC: Error processing prompt: ' . $ai_response->get_error_message());
            }
            return;
        }

        if ($debug_mode === '1') {
            error_log('CGPTFC: Got AI response, length: ' . strlen($ai_response));
        }

        // Get the model based on provider
        $model = '';
        if ($provider === 'gemini') {
            $model = get_option('cgptfc_gemini_model', 'gemini-pro');
        } else {
            $model = get_option('cgptfc_model', 'gpt-3.5-turbo');
        }

        // Save the response if logging is enabled
        $log_responses = get_post_meta($prompt_id, '_cgptfc_log_responses', true);
        if ($log_responses == '1') {
            if ($debug_mode === '1') {
                error_log('CGPTFC: Logging response to database');
            }

            // Use the enhanced response logger with proper provider and model information
            $result = cgptfc_main()->response_logger->log_response(
                    $prompt_id,
                    $entry_id,
                    $form->id,
                    $complete_prompt, // Save the actual prompt sent
                    $ai_response,
                    $provider, // Pass the correct provider
                    $model     // Pass the correct model
            );

            if ($result) {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Response logged successfully, ID: ' . $result);
                }
            } else {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Failed to log response');
                }
            }
        }

        // Handle the response according to settings
        $response_action = get_post_meta($prompt_id, '_cgptfc_response_action', true);

        // Send email if configured
        if ($response_action === 'email') {
            if ($debug_mode === '1') {
                error_log('CGPTFC: Sending email with response');
            }
            $email_sent = $this->send_email_response($prompt_id, $entry_id, $form_data, $ai_response, $provider);
            if ($email_sent) {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Email sent successfully');
                }
            } else {
                if ($debug_mode === '1') {
                    error_log('CGPTFC: Failed to send email');
                }
            }
        }
    }

    /**
     * Format all form data into a structured text for AI
     *
     * @param array $form_data The form data
     * @param int $prompt_id The prompt ID (for getting form field labels)
     * @return string Formatted form data as text
     */
    private function format_all_form_data($form_data, $prompt_id) {
        $output = __('Here is the submitted form data:', 'chatgpt-fluent-connector') . "\n\n";

        // Get field labels if possi$ai_responsee
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

    /**
     * Send email with the AI response - Updated to include provider info
     * 
     * @param int $prompt_id The prompt ID
     * @param int $entry_id The submission entry ID
     * @param array $form_data The submitted form data
     * @param string $ai_response The AI response
     * @param string $provider The AI provider used
     * @return bool True if email sent successfully, false otherwise
     */
    private function send_email_response($prompt_id, $entry_id, $form_data, $ai_response, $provider = 'openai') {
        // Get email settings
        $email_to = get_post_meta($prompt_id, '_cgptfc_email_to', true);
        $email_subject = get_post_meta($prompt_id, '_cgptfc_email_subject', true);
        $email_to_user = get_post_meta($prompt_id, '_cgptfc_email_to_user', true);

        $recipient_email = '';

        // First try to find an email field in the form if email_to_user is enabled
        if ($email_to_user == '1') {
            // Look for common email field names
            $common_email_fields = array('email', 'your_email', 'user_email', 'email_address', 'customer_email');

            foreach ($form_data as $field_key => $field_value) {
                // If the field name contains "email" and the value looks like an email
                if ((is_string($field_value) && filter_var($field_value, FILTER_VALIDATE_EMAIL)) &&
                        (strpos(strtolower($field_key), 'email') !== false || in_array(strtolower($field_key), $common_email_fields))) {
                    $recipient_email = $field_value;
                    break;
                }
            }

            // If no direct match found, try to look for nested arrays or complex field structures
            if (empty($recipient_email)) {
                foreach ($form_data as $field_key => $field_value) {
                    if (is_array($field_value)) {
                        foreach ($field_value as $sub_key => $sub_value) {
                            if (is_string($sub_value) && filter_var($sub_value, FILTER_VALIDATE_EMAIL)) {
                                $recipient_email = $sub_value;
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // If no email found in form or email_to_user not enabled, use email_to setting
        if (empty($recipient_email)) {
            // If email_to contains a placeholder, replace it with the form value
            if (!empty($email_to) && strpos($email_to, '{') !== false) {
                foreach ($form_data as $field_key => $field_value) {
                    if (is_string($field_value) && strpos($email_to, '{' . $field_key . '}') !== false) {
                        $email_to = str_replace('{' . $field_key . '}', $field_value, $email_to);
                    }
                }
            }

            $recipient_email = $email_to;
        }

        // If recipient_email is still empty, use admin email
        if (empty($recipient_email)) {
            $recipient_email = get_option('admin_email');
        }

        // Validate the email address
        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Set default subject if empty
        if (empty($email_subject)) {
            $email_subject = __('Response for Your Form Submission', 'chatgpt-fluent-connector');
        }

        // Format all form data for the email
        $form_data_html = '';
        $field_labels = $this->get_form_field_labels($prompt_id);

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
            $form_data_html .= '<div style="margin-bottom: 10px; padding-bottom: 10px; border-bottom: 1px solid #eee;">';
            $form_data_html .= '<strong>' . esc_html($label) . ':</strong> ' . esc_html($field_value);
            $form_data_html .= '</div>';
        }

        // Get provider name for display
        $provider_name = ($provider === 'gemini') ? 'Google Gemini' : 'ChatGPT';

        // Prepare email content
        $email_content = '
        <html>
        <head>
            <style>
                body {
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 100%;
                    margin: 0 auto;
                }
                .header {
                    background-color: #f5f5f5;
                    padding: 10px 20px;
                    border-radius: 5px 5px 0 0;
                    border-bottom: 1px solid #ddd;
                }
                .content {
                    padding: 20px;
                }

                .form-data {
                    margin-top: 20px;
                    padding: 15px;
                    background-color: #f5f5f5;
                    border-radius: 5px;
                }
                .form-data-title {
                    font-weight: bold;
                    margin-bottom: 10px;
                }
                .footer {
                    font-size: 12px;
                    color: #777;
                    border-top: 1px solid #ddd;
                    padding-top: 15px;
                    margin-top: 20px;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="content">                    
                    <div class="response">
                        ' . wp_kses_post(nl2br($ai_response)) . '
                    </div>
                </div>
            </div>
        </body>
        </html>';

        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');

        // Send the email
        $sent = wp_mail($recipient_email, $email_subject, $email_content, $headers);

        return $sent;
    }

    /**
     * Clean and prepare HTML response for display
     * 
     * @param string|WP_Error $response The response from the API
     * @return string The cleaned HTML response or error message
     */
    private function clean_html_response($response) {
        // First check if the response is an error
        if (is_wp_error($response)) {
            error_log("CGPTFC: Error in response: " . $response->get_error_message());
            return '<div class="cgptfc-error-message">' .
                    esc_html__('There was an error processing your request: ', 'chatgpt-fluent-connector') .
                    esc_html($response->get_error_message()) .
                    '</div>';
        }

        // Debug
        error_log("Original response length: " . strlen($response));
        error_log("First 100 chars: " . substr($response, 0, 100));

        // First, check if the response contains HTML code blocks
        if (preg_match('/```html\s*([\s\S]*?)\s*```/i', $response, $matches)) {
            // Extract the HTML from the code block
            $html = $matches[1];
            error_log("Extracted HTML from code block, length: " . strlen($html));
        } else {
            // If no code block found, use the entire response
            $html = $response;
            error_log("No code block found, using entire response");
        }

        // Remove <br> tags that might have been added
        $html = str_replace('<br>', '', $html);
        $html = str_replace('\n', '', $html);

        // Clean up other potential issues
        $html = trim($html);

        // Verify content is valid HTML - use a permissive approach
        if (strpos($html, '<') === false) {
            // If no HTML tags are found, wrap the content in a paragraph
            $html = '<p>' . nl2br($html) . '</p>';
            error_log("No HTML tags found, wrapped in paragraph");
        }

        // Add debug info for final result
        error_log("Final cleaned HTML length: " . strlen($html));
        error_log("First 100 chars of cleaned HTML: " . substr($html, 0, 100));

        return $html;
    }
}
