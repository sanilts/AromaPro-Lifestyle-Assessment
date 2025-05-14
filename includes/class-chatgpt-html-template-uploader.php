<?php
/**
 * Simplified HTML Template Uploader Class - Direct UI Fix
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_HTML_Template_Uploader {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add meta box for HTML template upload
        add_action('add_meta_boxes', array($this, 'add_template_upload_meta_box'));
        
        // Save HTML template
        add_action('save_post', array($this, 'save_html_template'), 10, 2);
        
        // Add filter to modify prompts with HTML templates
        add_filter('cgptfc_prepare_user_prompt', array($this, 'add_html_template_to_prompt'), 10, 3);
        
        // Add admin notices for debugging
        add_action('admin_notices', array($this, 'show_debug_notices'));
    }
    
    /**
     * Show debug notices on prompt edit screens
     */
    public function show_debug_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== 'cgptfc_prompt' || !in_array($screen->base, array('post', 'post-new'))) {
            return;
        }
        
        // Get current post ID
        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        if (!$post_id) {
            return;
        }
        
        // Get template info
        $use_html_template = get_post_meta($post_id, '_cgptfc_use_html_template', true);
        $html_template = get_post_meta($post_id, '_cgptfc_html_template', true);
        
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>HTML Template Status:</strong></p>';
        echo '<p>Template Enabled: ' . ($use_html_template == '1' ? 'Yes' : 'No') . '</p>';
        echo '<p>Template Length: ' . strlen($html_template) . ' characters</p>';
        if (strlen($html_template) > 0) {
            echo '<p>First 50 characters: <code>' . esc_html(substr($html_template, 0, 50)) . '...</code></p>';
        }
        echo '<p><em>Note: If you don\'t see the HTML textarea, please scroll down in the HTML Template box after choosing your file.</em></p>';
        echo '</div>';
    }
    
    /**
     * Add meta box for HTML template upload
     */
    public function add_template_upload_meta_box() {
        add_meta_box(
            'cgptfc_html_template',
            __('HTML Template Example', 'chatgpt-fluent-connector'),
            array($this, 'render_template_upload_meta_box'),
            'cgptfc_prompt',
            'normal',
            'high' // Set priority to high to appear near the top
        );
    }
    
    /**
     * Render HTML template upload meta box
     */
    public function render_template_upload_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('cgptfc_html_template_save', 'cgptfc_html_template_nonce');
        
        // Get saved values
        $use_html_template = get_post_meta($post->ID, '_cgptfc_use_html_template', true);
        $html_template = get_post_meta($post->ID, '_cgptfc_html_template', true);
        $template_instruction = get_post_meta($post->ID, '_cgptfc_template_instruction', true);
        
        if (empty($template_instruction)) {
            $template_instruction = __('Please format your response similar to this HTML example:', 'chatgpt-fluent-connector');
        }
        
        // Add inline styles for better UI
        echo '<style>
            .cgptfc-template-container {margin-bottom: 15px;}
            .cgptfc-file-upload {display: flex; align-items: center; margin-bottom: 10px;}
            .cgptfc-file-name {margin-left: 10px; color: #666;}
            .cgptfc-remove-btn {margin-left: 10px;}
            .cgptfc-textarea {width: 100%; min-height: 200px; font-family: monospace; margin-top: 10px;}
        </style>';
        
        // Add inline JavaScript
        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // Toggle visibility based on checkbox
                $('#cgptfc_use_html_template').change(function() {
                    if ($(this).is(':checked')) {
                        $('.cgptfc-template-fields').show();
                    } else {
                        $('.cgptfc-template-fields').hide();
                    }
                });
                
                // Handle file upload
                $('#cgptfc_template_file').change(function() {
                    var file = this.files[0];
                    if (file) {
                        // Show file name
                        $('.cgptfc-file-name').text(file.name);
                        
                        // Show remove button if not present
                        if ($('.cgptfc-remove-btn').length === 0) {
                            $('.cgptfc-file-upload').append('<button type="button" class="button cgptfc-remove-btn">Remove</button>');
                        }
                        
                        // Read file contents
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            $('#cgptfc_html_template').val(e.target.result).show();
                        };
                        reader.readAsText(file);
                    }
                });
                
                // Handle remove button click
                $(document).on('click', '.cgptfc-remove-btn', function() {
                    $('#cgptfc_template_file').val('');
                    $('.cgptfc-file-name').text('No file chosen');
                    $(this).remove();
                });
                
                // Make sure the form has the correct enctype
                $('form#post').attr('enctype', 'multipart/form-data');
                
                // Always show the textarea if there's content
                if ($('#cgptfc_html_template').val().length > 0) {
                    $('#cgptfc_html_template').show();
                }
            });
        </script>
        <?php
        
        // Render the form fields
        ?>
        <div class="cgptfc-template-container">
            <p>
                <label>
                    <input type="checkbox" name="cgptfc_use_html_template" id="cgptfc_use_html_template" value="1" <?php checked($use_html_template, '1'); ?>>
                    <?php _e('Include HTML template example in prompts', 'chatgpt-fluent-connector'); ?>
                </label>
            </p>
            <p class="description"><?php _e('When enabled, the HTML template will be included in the prompt sent to ChatGPT as an example of desired output formatting.', 'chatgpt-fluent-connector'); ?></p>
            
            <div class="cgptfc-template-fields" <?php echo ($use_html_template != '1') ? 'style="display:none;"' : ''; ?>>
                <p>
                    <label for="cgptfc_template_instruction"><strong><?php _e('Template Instruction:', 'chatgpt-fluent-connector'); ?></strong></label>
                    <input type="text" name="cgptfc_template_instruction" id="cgptfc_template_instruction" value="<?php echo esc_attr($template_instruction); ?>" class="widefat">
                    <span class="description"><?php _e('Instructions for ChatGPT about how to use the HTML template example.', 'chatgpt-fluent-connector'); ?></span>
                </p>
                
                <p><strong><?php _e('HTML Template:', 'chatgpt-fluent-connector'); ?></strong></p>
                <div class="cgptfc-file-upload">
                    <label class="button">
                        <?php _e('Choose File', 'chatgpt-fluent-connector'); ?>
                        <input type="file" id="cgptfc_template_file" style="display:none;" accept=".html,.htm">
                    </label>
                    <span class="cgptfc-file-name"><?php echo empty($html_template) ? __('No file chosen', 'chatgpt-fluent-connector') : __('Template loaded', 'chatgpt-fluent-connector'); ?></span>
                    <?php if (!empty($html_template)) : ?>
                        <button type="button" class="button cgptfc-remove-btn"><?php _e('Remove', 'chatgpt-fluent-connector'); ?></button>
                    <?php endif; ?>
                </div>
                
                <p><strong><?php _e('OR paste HTML directly:', 'chatgpt-fluent-connector'); ?></strong></p>
                <textarea name="cgptfc_html_template" id="cgptfc_html_template" class="cgptfc-textarea" <?php echo empty($html_template) ? '' : ''; ?>><?php echo esc_textarea($html_template); ?></textarea>
                
                <p class="description"><?php _e('Upload or paste an HTML template example to show ChatGPT the desired output format.', 'chatgpt-fluent-connector'); ?></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save HTML template
     */
    public function save_html_template($post_id, $post) {
        // Only save for our post type
        if ($post->post_type !== 'cgptfc_prompt') {
            return;
        }
        
        // Check if our nonce is set
        if (!isset($_POST['cgptfc_html_template_nonce'])) {
            return;
        }
        
        // Verify the nonce
        if (!wp_verify_nonce($_POST['cgptfc_html_template_nonce'], 'cgptfc_html_template_save')) {
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
        
        // Save the use HTML template option
        $use_html_template = isset($_POST['cgptfc_use_html_template']) ? '1' : '0';
        update_post_meta($post_id, '_cgptfc_use_html_template', $use_html_template);
        
        // Save the template instruction
        if (isset($_POST['cgptfc_template_instruction'])) {
            update_post_meta($post_id, '_cgptfc_template_instruction', sanitize_text_field($_POST['cgptfc_template_instruction']));
        }
        
        // Save the HTML template
        if (isset($_POST['cgptfc_html_template'])) {
            // We're deliberately using a less restrictive sanitization to preserve HTML structure
            $html_template = $this->sanitize_html_template($_POST['cgptfc_html_template']);
            
            // Add debug log
            error_log('Saving HTML template for prompt ID ' . $post_id . '. Length: ' . strlen($html_template));
            
            update_post_meta($post_id, '_cgptfc_html_template', $html_template);
        }
    }
    
    /**
     * Custom sanitization for HTML templates
     */
    private function sanitize_html_template($html) {
        // Basic sanitation to remove potentially dangerous scripts
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Remove on* attributes that could contain JavaScript
        $html = preg_replace('/\s+on\w+\s*=\s*(["\'])[^"\']*\1/i', '', $html);
        
        // Allow all other HTML elements and attributes
        return $html;
    }
    
    /**
     * Add HTML template to prompt
     */
    public function add_html_template_to_prompt($user_prompt, $prompt_id, $form_data) {
        // Check if prompt ID is valid
        if (empty($prompt_id) || !get_post($prompt_id)) {
            return $user_prompt;
        }
        
        // Check if HTML template is enabled
        $use_html_template = get_post_meta($prompt_id, '_cgptfc_use_html_template', true);
        
        if ($use_html_template != '1') {
            return $user_prompt;
        }
        
        // Get HTML template and instruction
        $html_template = get_post_meta($prompt_id, '_cgptfc_html_template', true);
        $template_instruction = get_post_meta($prompt_id, '_cgptfc_template_instruction', true);
        
        if (empty($html_template)) {
            return $user_prompt;
        }
        
        // Default instruction if empty
        if (empty($template_instruction)) {
            $template_instruction = __('Please format your response similar to this HTML example:', 'chatgpt-fluent-connector');
        }
        
        // Add the template instruction and HTML template to the prompt
        $user_prompt .= "\n\n" . $template_instruction . "\n\n```html\n" . $html_template . "\n```";
        
        // Log that we're adding an HTML template
        $debug_mode = get_option('cgptfc_debug_mode', '0');
        if ($debug_mode === '1') {
            error_log('Adding HTML template to prompt ID ' . $prompt_id . '. Template length: ' . strlen($html_template));
        }
        
        return $user_prompt;
    }
}