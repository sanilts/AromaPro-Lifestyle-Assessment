<?php
/**
 * Enhanced API Integration for ChatGPT & Gemini Fluent Forms Connector
 * 
 * This file contains the functions to add enhanced logging to the API classes
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add enhanced logging to the ChatGPT API process_form_with_prompt method
 */
function cgptfc_enhance_openai_api() {
    // Only proceed if the API class exists
    if (!class_exists('CGPTFC_API')) {
        return;
    }
    
    // Create a new method that wraps the original one but adds enhanced logging
    add_filter('cgptfc_before_process_form_with_prompt', 'cgptfc_before_process_form_with_prompt_openai', 10, 3);
    add_filter('cgptfc_after_process_form_with_prompt', 'cgptfc_after_process_form_with_prompt_openai', 10, 5);
}

/**
 * Add enhanced logging to the Gemini API process_form_with_prompt method
 */
function cgptfc_enhance_gemini_api() {
    // Only proceed if the Gemini API class exists
    if (!class_exists('CGPTFC_Gemini_API')) {
        return;
    }
    
    // Create a new method that wraps the original one but adds enhanced logging
    add_filter('cgptfc_before_process_form_with_prompt_gemini', 'cgptfc_before_process_form_with_prompt_gemini', 10, 3);
    add_filter('cgptfc_after_process_form_with_prompt_gemini', 'cgptfc_after_process_form_with_prompt_gemini', 10, 5);
}

/**
 * Hook that runs before processing a prompt with OpenAI
 * 
 * @param int $prompt_id The prompt ID
 * @param array $form_data The form data
 * @param int $entry_id The entry ID
 * @return array Timer data for this request
 */
function cgptfc_before_process_form_with_prompt_openai($prompt_id, $form_data, $entry_id) {
    // Start timing the request
    $timer_data = array(
        'start_time' => microtime(true),
        'prompt_id' => $prompt_id,
        'entry_id' => $entry_id
    );
    
    return $timer_data;
}

/**
 * Hook that runs after processing a prompt with OpenAI
 * 
 * @param string|WP_Error $response The API response or error
 * @param int $prompt_id The prompt ID
 * @param array $form_data The form data
 * @param int $entry_id The entry ID
 * @param array $timer_data Timer data from the before hook
 * @return string|WP_Error The original response (unchanged)
 */
function cgptfc_after_process_form_with_prompt_openai($response, $prompt_id, $form_data, $entry_id, $timer_data) {
    // Get the form ID
    $form_id = isset($form_data['_fluentform_id']) ? $form_data['_fluentform_id'] : 0;
    
    // Get prompt data
    $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
    $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
    $model = get_option('cgptfc_model', 'gpt-3.5-turbo');
    
    // Calculate execution time
    $end_time = microtime(true);
    $execution_time = isset($timer_data['start_time']) ? $end_time - $timer_data['start_time'] : null;
    
    // Build the complete prompt that was sent
    $complete_prompt = '';
    if (!empty($system_prompt)) {
        $complete_prompt .= "System Prompt:\n" . $system_prompt . "\n\n";
    }
    $complete_prompt .= "User Prompt:\n" . $user_prompt_template;
    
    // Only log if enabled for this prompt
    $log_responses = get_post_meta($prompt_id, '_cgptfc_log_responses', true);
    
    if ($log_responses == '1' && function_exists('cgptfc_main') && isset(cgptfc_main()->response_logger)) {
        // Log the response with enhanced details
        cgptfc_main()->response_logger->log_response(
            $prompt_id,
            $entry_id,
            $form_id,
            $complete_prompt,
            $response,
            'openai',
            $model,
            $execution_time
        );
    }
    
    return $response;
}

/**
 * Hook that runs before processing a prompt with Gemini
 * 
 * @param int $prompt_id The prompt ID
 * @param array $form_data The form data
 * @param int $entry_id The entry ID
 * @return array Timer data for this request
 */
function cgptfc_before_process_form_with_prompt_gemini($prompt_id, $form_data, $entry_id) {
    // Start timing the request
    $timer_data = array(
        'start_time' => microtime(true),
        'prompt_id' => $prompt_id,
        'entry_id' => $entry_id
    );
    
    return $timer_data;
}

/**
 * Hook that runs after processing a prompt with Gemini
 * 
 * @param string|WP_Error $response The API response or error
 * @param int $prompt_id The prompt ID
 * @param array $form_data The form data
 * @param int $entry_id The entry ID
 * @param array $timer_data Timer data from the before hook
 * @return string|WP_Error The original response (unchanged)
 */
function cgptfc_after_process_form_with_prompt_gemini($response, $prompt_id, $form_data, $entry_id, $timer_data) {
    // Get the form ID
    $form_id = isset($form_data['_fluentform_id']) ? $form_data['_fluentform_id'] : 0;
    
    // Get prompt data
    $system_prompt = get_post_meta($prompt_id, '_cgptfc_system_prompt', true);
    $user_prompt_template = get_post_meta($prompt_id, '_cgptfc_user_prompt_template', true);
    $model = get_option('cgptfc_gemini_model', 'gemini-pro');
    
    // Calculate execution time
    $end_time = microtime(true);
    $execution_time = isset($timer_data['start_time']) ? $end_time - $timer_data['start_time'] : null;
    
    // Build the complete prompt that was sent
    $complete_prompt = '';
    if (!empty($system_prompt)) {
        $complete_prompt .= "System Prompt:\n" . $system_prompt . "\n\n";
    }
    $complete_prompt .= "User Prompt:\n" . $user_prompt_template;
    
    // Only log if enabled for this prompt
    $log_responses = get_post_meta($prompt_id, '_cgptfc_log_responses', true);
    
    if ($log_responses == '1' && function_exists('cgptfc_main') && isset(cgptfc_main()->response_logger)) {
        // Log the response with enhanced details
        cgptfc_main()->response_logger->log_response(
            $prompt_id,
            $entry_id,
            $form_id,
            $complete_prompt,
            $response,
            'gemini',
            $model,
            $execution_time
        );
    }
    
    return $response;
}