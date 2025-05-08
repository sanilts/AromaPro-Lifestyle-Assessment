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
        add_filter('fluentform/submission_confirmation', array($this, 'maybe_display_response_on_confirmation'), 10, 3);
    }
    
    /**
     * Handle form submission
     * 
     * @param int $entry_id The submission entry ID
     * @param array $form_data The submitted form data
     * @param object $form The form object
     */
    public function handle_form_submission($entry_id, $form_data, $form) {
                
        $form_id = $form->id;
        
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
            return; // No prompts found for this form
        }

        // Process each prompt
        foreach ($prompts as $prompt) {
            $this->process_prompt($prompt->ID, $form_data, $entry_id, $form);
        }
    }

    /**
     * Process a prompt with form data
     * 
     * @param int $prompt_id The prompt ID
     * @param array $form_data The form data
     * @param int $entry_id The entry ID
     * @param object $form The form object
     * @return void
     */
    private function process_prompt($prompt_id, $form_data, $entry_id, $form) {
        // Get the API instance
        $api = cgptfc_main()->api;

        // Process the form with the prompt
        $ai_response = $api->process_form_with_prompt($prompt_id, $form_data);
        
        // Save the response if logging is enabled
        $log_responses = get_post_meta($prompt_id, '_cgptfc_log_responses', true);
        if ($log_responses == '1') {
            $result = cgptfc_main()->response_logger->log_response(
                $prompt_id, 
                $entry_id, 
                $form->id, 
                get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true),
                $ai_response
            );
        }

        // Handle the response according to settings
        $response_action = get_post_meta($prompt_id, '_cgptfc_response_action', true);

        if ($response_action === 'email') {
            $email_sent = $this->send_email_response($prompt_id, $entry_id, $form_data, $ai_response);
        }

        // Additional handling for showing response to user if enabled
        $show_to_user = get_post_meta($prompt_id, '_cgptfc_show_to_user', true);
        if ($show_to_user == '1') {
            // Add a filter to modify the confirmation message for this form
            add_filter('fluentform/submission_confirmation_' . $form->id, function($confirmation) use ($ai_response) {
                // If the confirmation is a success message type
                if (isset($confirmation['messageToShow'])) {
                    // Append the AI response to the confirmation message
                    $confirmation['messageToShow'] .= '<div class="chatgpt-response" style="margin-top: 20px; padding: 15px; background-color: #f9f9f9; border-left: 4px solid #0073aa;"><h3>' . __('AI Response:', 'chatgpt-fluent-connector') . '</h3><div>' . nl2br(esc_html($ai_response)) . '</div></div>';
                }
                return $confirmation;
            });
        }
    }
    
    /**
     * Send email with the AI response
     * 
     * @param int $prompt_id The prompt ID
     * @param int $entry_id The submission entry ID
     * @param array $form_data The submitted form data
     * @param string $ai_response The AI response
     * @return bool True if email sent successfully, false otherwise
     */
    private function send_email_response($prompt_id, $entry_id, $form_data, $ai_response) {
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
                error_log('CGPTFC: No direct email field found, checking nested fields');
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
            
            // Log the found or not found email
            if (!empty($recipient_email)) {
                error_log('CGPTFC: Will use email from form: ' . $recipient_email);
            } else {
                error_log('CGPTFC: No valid email found in form data');
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
            error_log('CGPTFC: No recipient email found, using admin email: ' . $recipient_email);
        }
        
        // Validate the email address
        if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
            error_log('CGPTFC: Invalid email address: ' . $recipient_email);
            return false;
        }
        
        // Set default subject if empty
        if (empty($email_subject)) {
            $email_subject = __('ChatGPT Response for Your Form Submission', 'chatgpt-fluent-connector');
        }
        
        // Prepare email content
        $email_content = '
        <html>
        <head>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                    border: 1px solid #ddd;
                    border-radius: 5px;
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
                .response {
                    background-color: #f9f9f9;
                    padding: 15px;
                    border-left: 4px solid #0073aa;
                    margin-bottom: 20px;
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
                <div class="header">
                    <h2>' . __('ChatGPT Response', 'chatgpt-fluent-connector') . '</h2>
                </div>
                <div class="content">
                    <p>' . __('Thank you for your submission. Here\'s the response from ChatGPT:', 'chatgpt-fluent-connector') . '</p>
                    
                    <div class="response">
                        ' . nl2br(esc_html($ai_response)) . '
                    </div>
                </div>
                <div class="footer">
                    ' . __('This is an automated email sent in response to your form submission.', 'chatgpt-fluent-connector') . '
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
     * Display ChatGPT response to the user
     * 
     * @param int $form_id The form ID
     * @param array $response_data The response data array from Fluent Forms
     * @return string The HTML to display
     */
    public function display_response_to_user($form_id, $response_data) {
        // Check if we have an entry ID
        if (empty($response_data['entry_id'])) {
            return '';
        }

        $entry_id = $response_data['entry_id'];

        // Get the AI response from our logs
        global $wpdb;
        $table_name = $wpdb->prefix . 'cgptfc_response_logs';

        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            return '';
        }

        // Get the most recent response for this form entry
        $response = $wpdb->get_var($wpdb->prepare(
            "SELECT ai_response FROM {$table_name} WHERE form_id = %d AND entry_id = %d ORDER BY created_at DESC LIMIT 1",
            $form_id, $entry_id
        ));

        if (empty($response)) {
            return '';
        }

        // Build the HTML
        $html = '<div class="cgptfc-response">';
        $html .= '<h3>' . esc_html__('AI Response', 'chatgpt-fluent-connector') . '</h3>';
        $html .= '<div class="cgptfc-response-content">';
        $html .= nl2br(esc_html($response));
        $html .= '</div>';
        $html .= '</div>';

        // Add some basic styling
        $html .= '<style>
            .cgptfc-response {
                margin: 20px 0;
                padding: 20px;
                background-color: #f9f9f9;
                border-left: 5px solid #0073aa;
                border-radius: 3px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .cgptfc-response h3 {
                margin-top: 0;
                color: #0073aa;
            }
            .cgptfc-response-content {
                line-height: 1.6;
            }
        </style>';

        return $html;
    }
    
    /**
     * Possibly display response on confirmation page
     * 
     * @param array $confirmation The confirmation data
     * @param array $form_data The form data
     * @param object $form The form object
     * @return array Modified confirmation data
     */
    public function maybe_display_response_on_confirmation($confirmation, $form_data, $form) {
        // If we have entry ID and this is a success message type
        if (!empty($form_data['entry_id']) && isset($confirmation['messageToShow'])) {
            $html = $this->display_response_to_user($form->id, ['entry_id' => $form_data['entry_id']]);
            if (!empty($html)) {
                $confirmation['messageToShow'] .= $html;
            }
        }
        return $confirmation;
    }
}