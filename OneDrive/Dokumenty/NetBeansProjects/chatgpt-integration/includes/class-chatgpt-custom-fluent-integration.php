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
        add_action('fluentform/submission/inserted', array($this, 'handle_form_submission'), 10, 3);
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
     * @param array $form_data The submitted form data
     * @param int $entry_id The submission entry ID
     * @param object $form The form object
     */
    private function process_prompt($prompt_id, $form_data, $entry_id, $form) {
        // Get the API instance
        $api = cgptfc_main()->api;
        
        // Process the form with the prompt
        $ai_response = $api->process_form_with_prompt($prompt_id, $form_data);
        
        if (is_wp_error($ai_response)) {
            // Log the error
            error_log('ChatGPT API Error: ' . $ai_response->get_error_message());
            return;
        }
        
        // Save the response if logging is enabled
        $log_responses = get_post_meta($prompt_id, '_cgptfc_log_responses', true);
        if ($log_responses == '1') {
            cgptfc_main()->response_logger->log_response(
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
            $this->send_email_response($prompt_id, $entry_id, $form_data, $ai_response);
        }
    }
    
    /**
     * Send email with the AI response
     * 
     * @param int $prompt_id The prompt ID
     * @param int $entry_id The submission entry ID
     * @param array $form_data The submitted form data
     * @param string $ai_response The AI response
     */
    private function send_email_response($prompt_id, $entry_id, $form_data, $ai_response) {
        // Get email settings
        $email_to = get_post_meta($prompt_id, '_cgptfc_email_to', true);
        $email_subject = get_post_meta($prompt_id, '_cgptfc_email_subject', true);
        
        // If email_to contains a placeholder, replace it with the form value
        if (!empty($email_to) && strpos($email_to, '{') !== false) {
            foreach ($form_data as $field_key => $field_value) {
                if (is_string($field_value) && strpos($email_to, '{' . $field_key . '}') !== false) {
                    $email_to = str_replace('{' . $field_key . '}', $field_value, $email_to);
                }
            }
        }
        
        // If email_to is still empty, use admin email
        if (empty($email_to)) {
            $email_to = get_option('admin_email');
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
                    <p>' . __('A form was submitted on your website, and ChatGPT has generated a response:', 'chatgpt-fluent-connector') . '</p>
                    
                    <div class="response">
                        ' . nl2br(esc_html($ai_response)) . '
                    </div>
                    
                    <p><strong>' . __('Form Entry ID:', 'chatgpt-fluent-connector') . '</strong> ' . esc_html($entry_id) . '</p>
                </div>
                <div class="footer">
                    ' . __('This is an automated email sent by the ChatGPT WordPress Connector plugin.', 'chatgpt-fluent-connector') . '
                </div>
            </div>
        </body>
        </html>';
        
        // Set email headers
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        // Send the email
        wp_mail($email_to, $email_subject, $email_content, $headers);
    }
}