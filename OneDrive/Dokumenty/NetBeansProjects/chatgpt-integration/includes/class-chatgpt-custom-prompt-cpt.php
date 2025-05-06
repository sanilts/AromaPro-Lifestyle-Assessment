<?php
/**
 * ChatGPT Prompt Custom Post Type
 * 
 * Handles registration and management of the ChatGPT Prompt custom post type
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Prompt_CPT {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Register custom post type
        add_action('init', array($this, 'register_post_type'));
        
        // Add meta boxes
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save post meta
        add_action('save_post', array($this, 'save_post_meta'));
    }
    
    /**
     * Register custom post type for ChatGPT prompts
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('ChatGPT Prompts', 'post type general name', 'chatgpt-fluent-connector'),
            'singular_name'      => _x('ChatGPT Prompt', 'post type singular name', 'chatgpt-fluent-connector'),
            'menu_name'          => _x('ChatGPT Prompts', 'admin menu', 'chatgpt-fluent-connector'),
            'add_new'            => _x('Add New', 'prompt', 'chatgpt-fluent-connector'),
            'add_new_item'       => __('Add New Prompt', 'chatgpt-fluent-connector'),
            'edit_item'          => __('Edit Prompt', 'chatgpt-fluent-connector'),
            'new_item'           => __('New Prompt', 'chatgpt-fluent-connector'),
            'view_item'          => __('View Prompt', 'chatgpt-fluent-connector'),
            'search_items'       => __('Search Prompts', 'chatgpt-fluent-connector'),
            'not_found'          => __('No prompts found', 'chatgpt-fluent-connector'),
            'not_found_in_trash' => __('No prompts found in Trash', 'chatgpt-fluent-connector'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-format-chat',
            'capability_type'     => 'post',
            'hierarchical'        => false,
            'supports'            => array('title'),
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'menu_position'       => 30,
            'show_in_rest'        => false,
        );
        
        register_post_type('cgptfc_prompt', $args);
    }
    
    /**
     * Add meta boxes for prompt settings
     */
    public function add_meta_boxes() {
        add_meta_box(
            'cgptfc_prompt_settings',
            __('Prompt Settings', 'chatgpt-fluent-connector'),
            array($this, 'render_prompt_settings_meta_box'),
            'cgptfc_prompt',
            'normal',
            'high'
        );
        
        add_meta_box(
            'cgptfc_form_selection',
            __('Fluent Form Selection', 'chatgpt-fluent-connector'),
            array($this, 'render_form_selection_meta_box'),
            'cgptfc_prompt',
            'side',
            'default'
        );
        
        add_meta_box(
            'cgptfc_response_handling',
            __('Response Handling', 'chatgpt-fluent-connector'),
            array($this, 'render_response_handling_meta_box'),
            'cgptfc_prompt',
            'normal',
            'default'
        );
    }
    
    /**
     * Render prompt settings meta box
     */
    public function render_prompt_settings_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('cgptfc_prompt_meta_save', 'cgptfc_prompt_nonce');
        
        // Get saved values
        $system_prompt = get_post_meta($post->ID, '_cgptfc_system_prompt', true);
        $user_prompt_template = get_post_meta($post->ID, '_cgptfc_user_prompt_template', true);
        $temperature = get_post_meta($post->ID, '_cgptfc_temperature', true);
        $prompt_type = get_post_meta($post->ID, '_cgptfc_prompt_type', true);
        
        if (empty($prompt_type)) {
            $prompt_type = 'template'; // Default to template
        }
        
        if (empty($temperature)) {
            $temperature = 0.7;
        }
        $max_tokens = get_post_meta($post->ID, '_cgptfc_max_tokens', true);
        if (empty($max_tokens)) {
            $max_tokens = 500;
        }
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="cgptfc_system_prompt"><?php _e('System Prompt:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <textarea name="cgptfc_system_prompt" id="cgptfc_system_prompt" class="large-text code" rows="3"><?php echo esc_textarea($system_prompt); ?></textarea>
                    <p class="description"><?php _e('Instructions that define how ChatGPT should behave (e.g., "You are a helpful assistant that specializes in...")', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label><?php _e('Prompt Type:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="cgptfc_prompt_type" value="template" <?php checked($prompt_type, 'template'); ?> class="prompt-type-radio">
                        <?php _e('Use custom template', 'chatgpt-fluent-connector'); ?>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="cgptfc_prompt_type" value="all_form_data" <?php checked($prompt_type, 'all_form_data'); ?> class="prompt-type-radio">
                        <?php _e('Send all form questions and answers', 'chatgpt-fluent-connector'); ?>
                    </label>
                    <p class="description"><?php _e('If selected, all form field labels and values will be sent to ChatGPT in a structured format.', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            <tr id="template-row" <?php echo ($prompt_type != 'template') ? 'style="display:none;"' : ''; ?>>
                <th><label for="cgptfc_user_prompt_template"><?php _e('User Prompt Template:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <textarea name="cgptfc_user_prompt_template" id="cgptfc_user_prompt_template" class="large-text code" rows="5"><?php echo esc_textarea($user_prompt_template); ?></textarea>
                    <p class="description">
                        <?php _e('The template for the user\'s message. You can use form field placeholders like {field_key} to insert form data.', 'chatgpt-fluent-connector'); ?><br>
                        <?php _e('Example: "Please analyze the following information: Name: {name}, Email: {email}, Message: {message}"', 'chatgpt-fluent-connector'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="cgptfc_temperature"><?php _e('Temperature:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="range" min="0" max="2" step="0.1" name="cgptfc_temperature" id="cgptfc_temperature" value="<?php echo esc_attr($temperature); ?>" oninput="document.getElementById('temp_value').innerHTML = this.value">
                    <span id="temp_value"><?php echo esc_html($temperature); ?></span>
                    <p class="description"><?php _e('Controls randomness: 0 is focused and deterministic, 1 is balanced, 2 is more random and creative', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="cgptfc_max_tokens"><?php _e('Max Tokens:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="number" min="50" max="4000" step="50" name="cgptfc_max_tokens" id="cgptfc_max_tokens" value="<?php echo esc_attr($max_tokens); ?>" class="small-text">
                    <p class="description"><?php _e('Maximum length of the response (1 token ≈ 4 characters)', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
        </table>
        <script>
            jQuery(document).ready(function($) {
                // Temperature slider update
                document.getElementById('cgptfc_temperature').addEventListener('input', function() {
                    document.getElementById('temp_value').innerHTML = this.value;
                });
                
                // Toggle template visibility based on prompt type
                $('.prompt-type-radio').change(function() {
                    if ($(this).val() === 'template') {
                        $('#template-row').show();
                    } else {
                        $('#template-row').hide();
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Render form selection meta box
     */
    public function render_form_selection_meta_box($post) {
        // Get all Fluent Forms
        $fluent_forms = array();
        
        if (function_exists('wpFluent')) {
            $forms = wpFluent()->table('fluentform_forms')
                ->select(['id', 'title'])
                ->orderBy('id', 'DESC')
                ->get();
            
            if ($forms) {
                foreach ($forms as $form) {
                    $fluent_forms[$form->id] = $form->title;
                }
            }
        }
        
        // Get saved form ID
        $selected_form_id = get_post_meta($post->ID, '_cgptfc_fluent_form_id', true);
        
        ?>
        <p>
            <?php if (empty($fluent_forms)) : ?>
                <div class="notice notice-warning inline">
                    <p><?php _e('No Fluent Forms found. Please create at least one form first.', 'chatgpt-fluent-connector'); ?></p>
                </div>
            <?php else : ?>
                <label for="cgptfc_fluent_form_id"><?php _e('Select Form:', 'chatgpt-fluent-connector'); ?></label><br>
                <select name="cgptfc_fluent_form_id" id="cgptfc_fluent_form_id" class="widefat">
                    <option value=""><?php _e('-- Select a form --', 'chatgpt-fluent-connector'); ?></option>
                    <?php foreach ($fluent_forms as $form_id => $form_title) : ?>
                        <option value="<?php echo esc_attr($form_id); ?>" <?php selected($selected_form_id, $form_id); ?>>
                            <?php echo esc_html($form_title); ?> (ID: <?php echo esc_html($form_id); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
        </p>
        
        <?php if (!empty($selected_form_id)) : ?>
            <p>
                <strong><?php _e('Available Form Fields:', 'chatgpt-fluent-connector'); ?></strong><br>
                <div style="max-height: 200px; overflow-y: auto; margin-top: 5px; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                    <?php
                    // Try multiple field storage methods in Fluent Forms
                    $fields_found = false;
                    
                    // Method 1: Try formDatenation first (older versions)
                    if (function_exists('wpFluent')) {
                        $formFields = wpFluent()->table('fluentform_form_meta')
                            ->where('form_id', $selected_form_id)
                            ->where('meta_key', 'formDatenation')
                            ->first();
                            
                        if ($formFields && !empty($formFields->value)) {
                            $fields = json_decode($formFields->value, true);
                            if (!empty($fields['fields'])) {
                                echo '<ul style="margin-top: 0;">';
                                foreach ($fields['fields'] as $field) {
                                    if (!empty($field['element']) && !empty($field['attributes']['name'])) {
                                        echo '<li><code>{' . esc_html($field['attributes']['name']) . '}</code> - ' . esc_html($field['element']) . '</li>';
                                    }
                                }
                                echo '</ul>';
                                $fields_found = true;
                            }
                        }
                    }
                    
                    // Method 2: Try form_fields_meta (newer versions)
                    if (!$fields_found && function_exists('wpFluent')) {
                        $formFields = wpFluent()->table('fluentform_form_meta')
                            ->where('form_id', $selected_form_id)
                            ->where('meta_key', 'form_fields_meta')
                            ->first();
                            
                        if ($formFields && !empty($formFields->value)) {
                            $fields = json_decode($formFields->value, true);
                            if (!empty($fields)) {
                                echo '<ul style="margin-top: 0;">';
                                foreach ($fields as $field) {
                                    if (!empty($field['element']) && !empty($field['attributes']['name'])) {
                                        echo '<li><code>{' . esc_html($field['attributes']['name']) . '}</code> - ' . esc_html($field['element']) . '</li>';
                                    }
                                }
                                echo '</ul>';
                                $fields_found = true;
                            }
                        }
                    }
                    
                    // Method 3: Try direct form structure (fallback)
                    if (!$fields_found && function_exists('wpFluent')) {
                        $form = wpFluent()->table('fluentform_forms')
                            ->where('id', $selected_form_id)
                            ->first();
                        
                        if ($form && !empty($form->form_fields)) {
                            $formFields = json_decode($form->form_fields, true);
                            
                            if (!empty($formFields['fields'])) {
                                echo '<ul style="margin-top: 0;">';
                                foreach ($formFields['fields'] as $field) {
                                    if (!empty($field['element']) && !empty($field['attributes']['name'])) {
                                        echo '<li><code>{' . esc_html($field['attributes']['name']) . '}</code> - ' . esc_html($field['element']) . '</li>';
                                    }
                                }
                                echo '</ul>';
                                $fields_found = true;
                            }
                        }
                    }
                    
                    // Method 4: Use Fluent Forms API if available (most reliable)
                    if (!$fields_found && class_exists('\FluentForm\App\Api\FormFields')) {
                        try {
                            $formFields = (new \FluentForm\App\Api\FormFields())->getFormInputs($selected_form_id);
                            if (!empty($formFields)) {
                                echo '<ul style="margin-top: 0;">';
                                foreach ($formFields as $fieldName => $fieldDetails) {
                                    echo '<li><code>{' . esc_html($fieldName) . '}</code> - ' . esc_html($fieldDetails['element']) . '</li>';
                                }
                                echo '</ul>';
                                $fields_found = true;
                            }
                        } catch (\Exception $e) {
                            // Silently fail, we'll show the default message below
                        }
                    }
                    
                    // If no fields found with any method
                    if (!$fields_found) {
                        echo '<div class="notice notice-info inline"><p>' . esc_html__('To see available form fields, please edit and save the selected form in Fluent Forms first.', 'chatgpt-fluent-connector') . '</p>';
                        echo '<p>' . esc_html__('Alternatively, you can manually determine field keys by checking the form structure in Fluent Forms.', 'chatgpt-fluent-connector') . '</p></div>';
                        
                        // Add link to edit the form
                        $edit_link = admin_url('admin.php?page=fluent_forms&route=editor&form_id=' . $selected_form_id);
                        echo '<p><a href="' . esc_url($edit_link) . '" class="button" target="_blank">' . esc_html__('Edit Form in Fluent Forms', 'chatgpt-fluent-connector') . '</a></p>';
                    }
                    ?>
                </div>
            </p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Render response handling meta box
     */
    public function render_response_handling_meta_box($post) {
        $response_action = get_post_meta($post->ID, '_cgptfc_response_action', true);
        if (empty($response_action)) {
            $response_action = 'email';
        }
        
        $email_to = get_post_meta($post->ID, '_cgptfc_email_to', true);
        $email_subject = get_post_meta($post->ID, '_cgptfc_email_subject', true);
        $log_responses = get_post_meta($post->ID, '_cgptfc_log_responses', true);
        $show_to_user = get_post_meta($post->ID, '_cgptfc_show_to_user', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('What to do with the response:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="radio" name="cgptfc_response_action" value="email" <?php checked($response_action, 'email'); ?>>
                        <?php _e('Send via email', 'chatgpt-fluent-connector'); ?>
                    </label><br>
                    
                    <label>
                        <input type="radio" name="cgptfc_response_action" value="store" <?php checked($response_action, 'store'); ?>>
                        <?php _e('Store only (no email)', 'chatgpt-fluent-connector'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th><label for="cgptfc_show_to_user"><?php _e('Show Response to User:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="cgptfc_show_to_user" id="cgptfc_show_to_user" value="1" <?php checked($show_to_user, '1'); ?>>
                        <?php _e('Show ChatGPT response on form confirmation page', 'chatgpt-fluent-connector'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the AI response will be displayed to the user on the form confirmation message.', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            
            <tr class="email-settings" <?php echo ($response_action != 'email') ? 'style="display:none;"' : ''; ?>>
                <th><label for="cgptfc_email_to"><?php _e('Email To:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="text" name="cgptfc_email_to" id="cgptfc_email_to" value="<?php echo esc_attr($email_to); ?>" class="regular-text">
                    <p class="description"><?php _e('Leave blank to use admin email. You can use form field placeholders like {email}.', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            
            <tr class="email-settings" <?php echo ($response_action != 'email') ? 'style="display:none;"' : ''; ?>>
                <th><label for="cgptfc_email_subject"><?php _e('Email Subject:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="text" name="cgptfc_email_subject" id="cgptfc_email_subject" value="<?php echo esc_attr($email_subject); ?>" class="regular-text">
                    <p class="description"><?php _e('Default: "ChatGPT Response for Your Form Submission"', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th><label for="cgptfc_log_responses"><?php _e('Log Responses:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="cgptfc_log_responses" id="cgptfc_log_responses" value="1" <?php checked($log_responses, '1'); ?>>
                        <?php _e('Save responses to the database', 'chatgpt-fluent-connector'); ?>
                    </label>
                    <p class="description"><?php _e('Useful for debugging and analytics', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>
        </table>
        
        <script>
            jQuery(document).ready(function($) {
                $('input[name="cgptfc_response_action"]').change(function() {
                    if ($(this).val() === 'email') {
                        $('.email-settings').show();
                    } else {
                        $('.email-settings').hide();
                    }
                });
            });
        </script>
        <?php
    }
    
    /**
     * Save post meta
     */
    public function save_post_meta($post_id) {
        // Check if our nonce is set
        if (!isset($_POST['cgptfc_prompt_nonce'])) {
            return;
        }
        
        // Verify the nonce
        if (!wp_verify_nonce($_POST['cgptfc_prompt_nonce'], 'cgptfc_prompt_meta_save')) {
            return;
        }
        
        // If this is an autosave, our form has not been submitted, so we don't want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check the user's permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save the prompt settings
        if (isset($_POST['cgptfc_system_prompt'])) {
            update_post_meta($post_id, '_cgptfc_system_prompt', sanitize_textarea_field($_POST['cgptfc_system_prompt']));
        }
        
        if (isset($_POST['cgptfc_user_prompt_template'])) {
            update_post_meta($post_id, '_cgptfc_user_prompt_template', sanitize_textarea_field($_POST['cgptfc_user_prompt_template']));
        }
        
        if (isset($_POST['cgptfc_temperature'])) {
            update_post_meta($post_id, '_cgptfc_temperature', floatval($_POST['cgptfc_temperature']));
        }
        
        if (isset($_POST['cgptfc_max_tokens'])) {
            update_post_meta($post_id, '_cgptfc_max_tokens', intval($_POST['cgptfc_max_tokens']));
        }
        
        // Save form selection
        if (isset($_POST['cgptfc_fluent_form_id'])) {
            update_post_meta($post_id, '_cgptfc_fluent_form_id', sanitize_text_field($_POST['cgptfc_fluent_form_id']));
        }
        
        // Save response handling
        if (isset($_POST['cgptfc_response_action'])) {
            update_post_meta($post_id, '_cgptfc_response_action', sanitize_text_field($_POST['cgptfc_response_action']));
        }
        
        if (isset($_POST['cgptfc_email_to'])) {
            update_post_meta($post_id, '_cgptfc_email_to', sanitize_text_field($_POST['cgptfc_email_to']));
        }
        
        if (isset($_POST['cgptfc_email_subject'])) {
            update_post_meta($post_id, '_cgptfc_email_subject', sanitize_text_field($_POST['cgptfc_email_subject']));
        }
        
        $log_responses = isset($_POST['cgptfc_log_responses']) ? '1' : '0';
        update_post_meta($post_id, '_cgptfc_log_responses', $log_responses);
        
        // Save the "Show to user" option
        $show_to_user = isset($_POST['cgptfc_show_to_user']) ? '1' : '0';
        update_post_meta($post_id, '_cgptfc_show_to_user', $show_to_user);
        
        // Add this to your save_post_meta function
        if (isset($_POST['cgptfc_prompt_type'])) {
            update_post_meta($post_id, '_cgptfc_prompt_type', sanitize_text_field($_POST['cgptfc_prompt_type']));
        }
    }
}