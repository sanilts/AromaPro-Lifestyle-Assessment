<?php
/**
 * HTML Template Uploader Class
 * 
 * Handles uploading and integration of HTML templates for ChatGPT prompts
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
        add_filter('cgptfc_process_form_with_prompt', array($this, 'append_html_template_to_prompt'), 10, 3);
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        $screen = get_current_screen();
        
        // Only load on our custom post type edit page
        if ($screen->post_type !== 'cgptfc_prompt') {
            return;
        }
        
        // Enqueue our CSS
        wp_enqueue_style(
            'cgptfc-template-uploader',
            CGPTFC_URL . 'assets/css/template-uploader.css',
            array(),
            CGPTFC_VERSION
        );
        
        // Enqueue our JavaScript
        wp_enqueue_script(
            'cgptfc-template-uploader',
            CGPTFC_URL . 'assets/js/template-uploader.js',
            array('jquery'),
            CGPTFC_VERSION,
            true
        );
        
        // Pass translations to JavaScript
        wp_localize_script('cgptfc-template-uploader', 'cgptfc_uploader', array(
            'strings' => array(
                'remove' => __('Remove', 'chatgpt-fluent-connector'),
                'no_file' => __('No file chosen', 'chatgpt-fluent-connector')
            )
        ));
    }
    
    /**
     * Add meta box for HTML template upload
     */
    public function add_template_upload_meta_box() {
        add_meta_box(
            'cgptfc_html_template_box',
            __('HTML Template', 'chatgpt-fluent-connector'),
            array($this, 'render_template_upload_meta_box'),
            'cgptfc_prompt',
            'normal',
            'default'
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
        
        // Default instruction if empty
        if (empty($template_instruction)) {
            $template_instruction = __('Please format your response using this HTML template as a reference:', 'chatgpt-fluent-connector');
        }
        
        ?>
        <div class="cgptfc-template-uploader-wrapper">
            <p>
                <label>
                    <input type="checkbox" name="cgptfc_use_html_template" id="cgptfc_use_html_template" value="1" <?php checked($use_html_template, '1'); ?>>
                    <?php _e('Include an HTML template example in the prompt', 'chatgpt-fluent-connector'); ?>
                </label>
            </p>
            <p class="description">
                <?php _e('When enabled, the HTML template will be included in the prompt sent to ChatGPT to guide the formatting of its response.', 'chatgpt-fluent-connector'); ?>
            </p>
            
            <div id="html_template_row" <?php echo ($use_html_template != '1') ? 'style="display:none;"' : ''; ?>>
                <p>
                    <label for="cgptfc_template_instruction"><strong><?php _e('Template Instruction:', 'chatgpt-fluent-connector'); ?></strong></label>
                    <input type="text" name="cgptfc_template_instruction" id="cgptfc_template_instruction" value="<?php echo esc_attr($template_instruction); ?>" class="widefat">
                    <span class="description"><?php _e('This text will be shown to ChatGPT to explain how to use the template.', 'chatgpt-fluent-connector'); ?></span>
                </p>
                
                <div id="template_instruction_row">
                    <p><strong><?php _e('Upload HTML Template:', 'chatgpt-fluent-connector'); ?></strong></p>
                    <div class="cgptfc-template-upload-field">
                        <input type="file" id="cgptfc_html_template_file" accept=".html,.htm">
                        <span class="cgptfc-template-filename"><?php echo empty($html_template) ? __('No file chosen', 'chatgpt-fluent-connector') : __('Template loaded', 'chatgpt-fluent-connector'); ?></span>
                        <?php if (!empty($html_template)) : ?>
                            <button type="button" class="button cgptfc-remove-template"><?php _e('Remove', 'chatgpt-fluent-connector'); ?></button>
                        <?php endif; ?>
                    </div>
                    
                    <p><strong><?php _e('OR edit HTML directly:', 'chatgpt-fluent-connector'); ?></strong></p>
                    <textarea name="cgptfc_html_template" id="cgptfc_html_template" class="widefat code" rows="10"><?php echo esc_textarea($html_template); ?></textarea>
                    
                    <div class="cgptfc-template-preview">
                        <h4><?php _e('Template Preview:', 'chatgpt-fluent-connector'); ?></h4>
                        <div class="cgptfc-preview-container">
                            <?php if (!empty($html_template)) : ?>
                                <iframe srcdoc="<?php echo esc_attr($html_template); ?>" style="width:100%; height:200px; border:none;"></iframe>
                            <?php else : ?>
                                <p><?php _e('Upload or enter HTML to see preview', 'chatgpt-fluent-connector'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save HTML template
     */
    public function save_html_template($post_id, $post) {
        // Check if our custom post type
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
        
        // Save the "use HTML template" option
        $use_html_template = isset($_POST['cgptfc_use_html_template']) ? '1' : '0';
        update_post_meta($post_id, '_cgptfc_use_html_template', $use_html_template);
        
        // Save the template instruction
        if (isset($_POST['cgptfc_template_instruction'])) {
            update_post_meta($post_id, '_cgptfc_template_instruction', sanitize_text_field($_POST['cgptfc_template_instruction']));
        }
        
        // Save the HTML template
        if (isset($_POST['cgptfc_html_template'])) {
            // Use KSES to sanitize HTML but allow tags needed for templates
            $allowed_html = wp_kses_allowed_html('post');
            // Add additional HTML elements typically needed in templates
            $allowed_html['style'] = array('type' => true);
            $allowed_html['script'] = array('type' => true); // Note: actual script content will still be filtered
            
            $html_template = wp_kses($_POST['cgptfc_html_template'], $allowed_html);
            update_post_meta($post_id, '_cgptfc_html_template', $html_template);
        }
    }
    
    /**
     * Append HTML template to prompt
     * This filter should be called in the API class before sending the prompt
     */
    public function append_html_template_to_prompt($user_prompt, $prompt_id, $form_data) {
        // Check if HTML template is enabled for this prompt
        $use_html_template = get_post_meta($prompt_id, '_cgptfc_use_html_template', true);
        
        if ($use_html_template != '1') {
            return $user_prompt;
        }
        
        // Get the HTML template and instruction
        $html_template = get_post_meta($prompt_id, '_cgptfc_html_template', true);
        $template_instruction = get_post_meta($prompt_id, '_cgptfc_template_instruction', true);
        
        // Don't modify prompt if template is empty
        if (empty($html_template)) {
            return $user_prompt;
        }
        
        // Default instruction if not set
        if (empty($template_instruction)) {
            $template_instruction = __('Please format your response using this HTML template as a reference:', 'chatgpt-fluent-connector');
        }
        
        // Add template to the prompt with proper formatting
        $template_addition = "\n\n{$template_instruction}\n\n```html\n{$html_template}\n```";
        
        // Log for debugging if enabled
        if (get_option('cgptfc_debug_mode') === '1') {
            error_log("CGPTFC: Adding HTML template to prompt ID {$prompt_id}. Template length: " . strlen($html_template));
        }
        
        return $user_prompt . $template_addition;
    }
}

// Initialize our HTML template uploader class
add_action('plugins_loaded', function() {
    if (class_exists('CGPTFC_Main')) {
        new CGPTFC_HTML_Template_Uploader();
    }
}, 20); // Run after main plugin initializes