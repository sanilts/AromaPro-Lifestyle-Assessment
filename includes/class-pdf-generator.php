<?php

/**
 * PDF Generator Class - Enhanced Emoji Support
 * 
 * Handles PDF generation from AI responses with proper emoji rendering using local mPDF library
 */
class SFAIC_PDF_Generator {

    /**
     * Constructor
     */
    public function __construct() {
        // Add meta box for PDF settings
        add_action('add_meta_boxes', array($this, 'add_pdf_settings_meta_box'));

        // Save PDF settings
        add_action('save_post', array($this, 'save_pdf_settings'), 10, 2);

        // Hook into form processing to generate PDFs
        add_action('sfaic_after_ai_response_processed', array($this, 'maybe_generate_pdf'), 10, 5);

        // Include mPDF library
        add_action('init', array($this, 'load_pdf_libraries'));

        // Hook into the AI response to fix encoding early
        add_filter('sfaic_ai_response', array($this, 'fix_response_encoding'), 5);
    }

    /**
     * Fix encoding issues in AI response early
     */
    public function fix_response_encoding($response) {
        if (is_string($response)) {
            return $this->fix_encoding_early($response);
        }
        return $response;
    }

    /**
     * Early encoding fix for corrupted UTF-8
     */
    private function fix_encoding_early($content) {
        // Fix double-encoded UTF-8
        $content = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $content);

        // Fix mojibake (double-encoded UTF-8)
        if (preg_match('/[\xC3][\x80-\xBF]/', $content)) {
            $fixed = @iconv('UTF-8', 'ISO-8859-1//IGNORE', $content);
            if ($fixed !== false && mb_check_encoding($fixed, 'UTF-8')) {
                $content = $fixed;
            }
        }

        // Fix specific corrupted patterns using regex
        $patterns = array(
            '/\xC3\xB0\xC5\x92[\x80-\xBF][\x80-\xBF]/' => '',
            '/Ã\x83Â©/' => 'é',
            '/Ã\x83Â¨/' => 'è',
            '/Ã\x83Â«/' => 'ë',
            '/Ã\x83Â¢/' => 'â',
            '/Ã\x83Â´/' => 'ô',
            '/Ã\x83Â®/' => 'î',
            '/Ã\x83Â§/' => 'ç',
            '/Ã\x83Â /' => 'à',
            '/Ã\x83Â¹/' => 'ù',
            '/Ã\x83â€°/' => 'É',
            '/Ã\x83â‚¬/' => 'À',
            '/Ã\x83Âª/' => 'ê',
            '/Ã\x83Â¯/' => 'ï',
            '/Ã\x83Â¼/' => 'ü',
            '/Ã\x83Â¶/' => 'ö',
            '/Ã\x83Â¤/' => 'ä',
        );

        foreach ($patterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        // Alternative approach: decode HTML entities if present
        if (strpos($content, '&') !== false) {
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Fix common UTF-8 issues
        $content = mb_convert_encoding($content, 'UTF-8', 'UTF-8');

        // Remove any remaining invalid UTF-8 sequences
        $content = @iconv('UTF-8', 'UTF-8//IGNORE', $content);

        // Ensure proper UTF-8
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }

        return $content;
    }

    /**
     * Enhanced emoji to image conversion with better fallbacks
     */
    private function convert_emojis_to_images($html) {
        // First fix encoding
        $html = $this->fix_encoding_early($html);

        // Comprehensive emoji pattern that covers more Unicode ranges
        $emoji_pattern = '/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]|[\x{1F900}-\x{1F9FF}]|[\x{1F000}-\x{1F02F}]|[\x{1F0A0}-\x{1F0FF}]|[\x{1F100}-\x{1F1FF}]|[\x{FE00}-\x{FE0F}]|[\x{1F200}-\x{1F2FF}]|[\x{E0020}-\x{E007F}]|[\x{2190}-\x{21FF}]|[\x{2000}-\x{206F}]|[\x{20A0}-\x{20CF}]|[\x{2100}-\x{214F}]|[\x{2150}-\x{218F}]|[\x{2460}-\x{24FF}]|[\x{25A0}-\x{25FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u';

        // Use a callback to replace all emojis with images or better fallbacks
        $html = preg_replace_callback(
                $emoji_pattern,
                array($this, 'emoji_to_image_enhanced'),
                $html
        );

        return $html;
    }

    /**
     * Enhanced emoji to image conversion with better fallbacks
     */
    private function emoji_to_image_enhanced($matches) {
        $emoji = $matches[0];

        // First try to get emoji image
        $image_html = $this->try_emoji_image($emoji);
        if ($image_html) {
            return $image_html;
        }

        // If image fails, use enhanced fallback system
        return $this->enhanced_emoji_fallback($emoji);
    }

    /**
     * Try to get emoji as image
     */
    private function try_emoji_image($emoji) {
        // Try multiple emoji image sources
        $sources = array(
            'noto' => $this->get_noto_emoji_url($emoji),
            'twemoji' => $this->get_twemoji_url($emoji),
            'openmoji' => $this->get_openmoji_url($emoji)
        );

        foreach ($sources as $source_name => $url) {
            if ($url) {
                $image_data = $this->get_emoji_image_base64($url, $emoji, $source_name);
                if ($image_data) {
                    return '<img src="' . $image_data . '" style="width: 1.2em; height: 1.2em; vertical-align: middle; display: inline-block;" alt="' . htmlspecialchars($emoji) . '" />';
                }
            }
        }

        return false;
    }

    /**
     * Get Noto emoji URL
     */
    private function get_noto_emoji_url($emoji) {
        $codepoints = $this->emoji_to_codepoints($emoji);
        if ($codepoints) {
            return 'https://raw.githubusercontent.com/googlefonts/noto-emoji/main/png/128/emoji_u' . $codepoints . '.png';
        }
        return false;
    }

    /**
     * Get Twemoji URL
     */
    private function get_twemoji_url($emoji) {
        $codepoints = $this->emoji_to_codepoints($emoji, '-');
        if ($codepoints) {
            return 'https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/72x72/' . $codepoints . '.png';
        }
        return false;
    }

    /**
     * Get OpenMoji URL
     */
    private function get_openmoji_url($emoji) {
        $codepoints = $this->emoji_to_codepoints($emoji, '-');
        if ($codepoints) {
            return 'https://cdn.jsdelivr.net/npm/openmoji@latest/color/72x72/' . strtoupper($codepoints) . '.png';
        }
        return false;
    }

    /**
     * Convert emoji to Unicode codepoints
     */
    private function emoji_to_codepoints($emoji, $separator = '_') {
        $codepoints = [];
        $emoji = mb_convert_encoding($emoji, 'UTF-32', 'UTF-8');
        $length = mb_strlen($emoji, 'UTF-32');

        for ($i = 0; $i < $length; $i++) {
            $char = mb_substr($emoji, $i, 1, 'UTF-32');
            $codepoint = unpack('N', $char)[1];
            // Skip variation selectors and zero-width joiners
            if ($codepoint !== 0xFE0F && $codepoint !== 0xFE0E && $codepoint !== 0x200D) {
                $codepoints[] = sprintf('%04x', $codepoint);
            }
        }

        return empty($codepoints) ? false : implode($separator, $codepoints);
    }

    /**
     * Enhanced emoji image download with caching and error handling
     */
    private function get_emoji_image_base64($url, $emoji, $source = 'default') {
        // Try to get from cache first
        $cache_key = 'emoji_img_' . md5($emoji . $source);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Download the image with timeout and user agent
        $response = wp_remote_get($url, array(
            'timeout' => 3,
            'sslverify' => false,
            'user-agent' => 'Mozilla/5.0 (PDF Generator)',
            'headers' => array(
                'Accept' => 'image/png,image/*,*/*;q=0.8'
            )
        ));

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $image_data = wp_remote_retrieve_body($response);
            if (!empty($image_data) && strlen($image_data) > 100) { // Basic validation
                $base64 = 'data:image/png;base64,' . base64_encode($image_data);

                // Cache for 7 days
                set_transient($cache_key, $base64, 7 * DAY_IN_SECONDS);

                return $base64;
            }
        }

        return false;
    }

    /**
     * Enhanced emoji fallback system
     */
    private function enhanced_emoji_fallback($emoji) {
        // Comprehensive emoji fallback map
        $fallback_map = array(
            // Stars and sparkles
            '🌟' => '<span style="color: #FFD700; font-size: 1.3em;">★</span>',
            '⭐' => '<span style="color: #FFD700; font-size: 1.3em;">★</span>',
            '✨' => '<span style="color: #FFD700; font-size: 1.2em;">✦</span>',
            '💫' => '<span style="color: #87CEEB; font-size: 1.2em;">✧</span>',
            // Hearts
            '❤️' => '<span style="color: #FF0000; font-size: 1.3em;">♥</span>',
            '💙' => '<span style="color: #0000FF; font-size: 1.3em;">♥</span>',
            '💚' => '<span style="color: #00FF00; font-size: 1.3em;">♥</span>',
            '💛' => '<span style="color: #FFD700; font-size: 1.3em;">♥</span>',
            '🧡' => '<span style="color: #FF8C00; font-size: 1.3em;">♥</span>',
            '💜' => '<span style="color: #8A2BE2; font-size: 1.3em;">♥</span>',
            '🖤' => '<span style="color: #000000; font-size: 1.3em;">♥</span>',
            '🤍' => '<span style="color: #FFFFFF; font-size: 1.3em; text-shadow: 1px 1px 1px #ccc;">♥</span>',
            // Check marks and X marks
            '✅' => '<span style="color: #00FF00; font-size: 1.3em; font-weight: bold;">✓</span>',
            '✔️' => '<span style="color: #00FF00; font-size: 1.3em; font-weight: bold;">✓</span>',
            '❌' => '<span style="color: #FF0000; font-size: 1.3em; font-weight: bold;">✗</span>',
            '❎' => '<span style="color: #FF0000; font-size: 1.2em;">⊗</span>',
            '☑️' => '<span style="color: #00FF00; font-size: 1.2em;">☑</span>',
            // Circles and dots
            '🔴' => '<span style="color: #FF0000; font-size: 1.3em;">●</span>',
            '🟢' => '<span style="color: #00FF00; font-size: 1.3em;">●</span>',
            '🔵' => '<span style="color: #0000FF; font-size: 1.3em;">●</span>',
            '🟡' => '<span style="color: #FFD700; font-size: 1.3em;">●</span>',
            '🟠' => '<span style="color: #FF8C00; font-size: 1.3em;">●</span>',
            '🟣' => '<span style="color: #8A2BE2; font-size: 1.3em;">●</span>',
            '⚫' => '<span style="color: #000000; font-size: 1.3em;">●</span>',
            '⚪' => '<span style="color: #FFFFFF; font-size: 1.3em; text-shadow: 1px 1px 1px #ccc;">●</span>',
            // Arrows
            '⬆️' => '<span style="color: #000000; font-size: 1.3em;">↑</span>',
            '⬇️' => '<span style="color: #000000; font-size: 1.3em;">↓</span>',
            '➡️' => '<span style="color: #000000; font-size: 1.3em;">→</span>',
            '⬅️' => '<span style="color: #000000; font-size: 1.3em;">←</span>',
            '↗️' => '<span style="color: #000000; font-size: 1.3em;">↗</span>',
            '↘️' => '<span style="color: #000000; font-size: 1.3em;">↘</span>',
            '↙️' => '<span style="color: #000000; font-size: 1.3em;">↙</span>',
            '↖️' => '<span style="color: #000000; font-size: 1.3em;">↖</span>',
            // Tools and objects
            '💡' => '<span style="color: #FFD700; font-size: 1.3em;">💡</span>',
            '🔧' => '<span style="color: #A0A0A0; font-size: 1.3em;">🔧</span>',
            '⚙️' => '<span style="color: #A0A0A0; font-size: 1.3em;">⚙</span>',
            '🔑' => '<span style="color: #FFD700; font-size: 1.3em;">🗝</span>',
            '📌' => '<span style="color: #FF0000; font-size: 1.3em;">📌</span>',
            '📊' => '<span style="color: #4169E1; font-size: 1.3em;">📊</span>',
            '📈' => '<span style="color: #00FF00; font-size: 1.3em;">📈</span>',
            '📉' => '<span style="color: #FF0000; font-size: 1.3em;">📉</span>',
            // Nature
            '🌿' => '<span style="color: #228B22; font-size: 1.3em;">🌿</span>',
            '🌱' => '<span style="color: #90EE90; font-size: 1.3em;">🌱</span>',
            '🌳' => '<span style="color: #228B22; font-size: 1.3em;">🌳</span>',
            '🌺' => '<span style="color: #FF69B4; font-size: 1.3em;">🌺</span>',
            '🌸' => '<span style="color: #FFB6C1; font-size: 1.3em;">🌸</span>',
            // Fire and energy
            '🔥' => '<span style="color: #FF4500; font-size: 1.3em;">🔥</span>',
            '⚡' => '<span style="color: #FFD700; font-size: 1.3em;">⚡</span>',
            '✨' => '<span style="color: #FFD700; font-size: 1.2em;">✨</span>',
            // Targets and focus
            '🎯' => '<span style="color: #FF0000; font-size: 1.3em;">⊕</span>',
            '📍' => '<span style="color: #FF0000; font-size: 1.3em;">📍</span>',
            // Communication
            '📣' => '<span style="color: #4169E1; font-size: 1.3em;">📢</span>',
            '📢' => '<span style="color: #4169E1; font-size: 1.3em;">📢</span>',
            '📯' => '<span style="color: #DAA520; font-size: 1.3em;">📯</span>',
            // Gestures and body parts
            '👍' => '<span style="color: #FFE4B5; font-size: 1.3em;">👍</span>',
            '👎' => '<span style="color: #FFE4B5; font-size: 1.3em;">👎</span>',
            '👋' => '<span style="color: #FFE4B5; font-size: 1.3em;">👋</span>',
            '🤝' => '<span style="color: #FFE4B5; font-size: 1.3em;">🤝</span>',
            '👏' => '<span style="color: #FFE4B5; font-size: 1.3em;">👏</span>',
            // Brain and thinking
            '🧠' => '<span style="color: #FF69B4; font-size: 1.3em;">🧠</span>',
            '💭' => '<span style="color: #87CEEB; font-size: 1.3em;">💭</span>',
            '💡' => '<span style="color: #FFD700; font-size: 1.3em;">💡</span>',
            // Time and clock
            '⏰' => '<span style="color: #000000; font-size: 1.3em;">⏰</span>',
            '⏱️' => '<span style="color: #000000; font-size: 1.3em;">⏱</span>',
            '⏲️' => '<span style="color: #000000; font-size: 1.3em;">⏲</span>',
            // Warning and attention
            '⚠️' => '<span style="color: #FFD700; font-size: 1.3em;">⚠</span>',
            '🚨' => '<span style="color: #FF0000; font-size: 1.3em;">🚨</span>',
            '❗' => '<span style="color: #FF0000; font-size: 1.3em;">!</span>',
            '❓' => '<span style="color: #4169E1; font-size: 1.3em;">?</span>',
            // Rocket and movement
            '🚀' => '<span style="color: #A0A0A0; font-size: 1.3em;">🚀</span>',
            '✈️' => '<span style="color: #87CEEB; font-size: 1.3em;">✈</span>',
            // Documents and books
            '📖' => '<span style="color: #8B4513; font-size: 1.3em;">📖</span>',
            '📚' => '<span style="color: #8B4513; font-size: 1.3em;">📚</span>',
            '📝' => '<span style="color: #FFD700; font-size: 1.3em;">📝</span>',
            '📄' => '<span style="color: #FFFFFF; font-size: 1.3em; text-shadow: 1px 1px 1px #ccc;">📄</span>',
            // Crown and jewels
            '👑' => '<span style="color: #FFD700; font-size: 1.3em;">👑</span>',
            '💎' => '<span style="color: #00BFFF; font-size: 1.3em;">💎</span>',
            '🏆' => '<span style="color: #FFD700; font-size: 1.3em;">🏆</span>',
            // Smileys (simple ones)
            '😊' => '<span style="color: #FFD700; font-size: 1.3em;">☺</span>',
            '😃' => '<span style="color: #FFD700; font-size: 1.3em;">☺</span>',
            '😄' => '<span style="color: #FFD700; font-size: 1.3em;">☺</span>',
            '😁' => '<span style="color: #FFD700; font-size: 1.3em;">☺</span>',
            '🙂' => '<span style="color: #FFD700; font-size: 1.3em;">☺</span>',
            '😉' => '<span style="color: #FFD700; font-size: 1.3em;">😉</span>',
        );

        // Check if we have a specific fallback
        if (isset($fallback_map[$emoji])) {
            return $fallback_map[$emoji];
        }

        // Generic fallback based on emoji Unicode range
        $unicode_point = $this->get_first_unicode_point($emoji);

        if ($unicode_point) {
            // Smileys and People
            if ($unicode_point >= 0x1F600 && $unicode_point <= 0x1F64F) {
                return '<span style="color: #FFD700; font-size: 1.3em;">☺</span>';
            }
            // Animals and Nature
            elseif ($unicode_point >= 0x1F400 && $unicode_point <= 0x1F4FF) {
                return '<span style="color: #228B22; font-size: 1.3em;">🐾</span>';
            }
            // Food and Drink
            elseif ($unicode_point >= 0x1F300 && $unicode_point <= 0x1F3FF) {
                return '<span style="color: #8B4513; font-size: 1.3em;">🍽</span>';
            }
            // Transport and Map Symbols
            elseif ($unicode_point >= 0x1F680 && $unicode_point <= 0x1F6FF) {
                return '<span style="color: #4169E1; font-size: 1.3em;">🚗</span>';
            }
            // Miscellaneous Symbols
            elseif ($unicode_point >= 0x2600 && $unicode_point <= 0x26FF) {
                return '<span style="color: #000000; font-size: 1.3em;">●</span>';
            }
            // Dingbats
            elseif ($unicode_point >= 0x2700 && $unicode_point <= 0x27BF) {
                return '<span style="color: #000000; font-size: 1.3em;">✦</span>';
            }
        }

        // Final fallback - try to display the emoji as-is with appropriate font
        return '<span style="font-family: \'Segoe UI Emoji\', \'Apple Color Emoji\', \'Noto Color Emoji\', sans-serif; font-size: 1.2em;">' . $emoji . '</span>';
    }

    /**
     * Get the first Unicode code point of an emoji
     */
    private function get_first_unicode_point($emoji) {
        $emoji = mb_convert_encoding($emoji, 'UTF-32', 'UTF-8');
        if (mb_strlen($emoji, 'UTF-32') > 0) {
            $char = mb_substr($emoji, 0, 1, 'UTF-32');
            return unpack('N', $char)[1];
        }
        return false;
    }

    // ... [Rest of the existing methods remain the same] ...

    /**
     * Load PDF libraries
     */
    public function load_pdf_libraries() {
        $this->load_mpdf_library();
    }

    /**
     * Load mPDF library
     */
    private function load_mpdf_library() {
        $mpdf_path = SFAIC_DIR . 'vendor/mpdf/mpdf/src/Mpdf.php';

        if (!class_exists('Mpdf\Mpdf')) {
            // Check if mPDF is available via Composer
            if (file_exists(SFAIC_DIR . 'vendor/autoload.php')) {
                require_once SFAIC_DIR . 'vendor/autoload.php';
            } else {
                // Fallback: Check for manual mPDF installation
                if (file_exists($mpdf_path)) {
                    require_once $mpdf_path;
                } else {
                    // Add admin notice about missing mPDF
                    add_action('admin_notices', array($this, 'mpdf_missing_notice'));
                }
            }
        }
    }

    /**
     * Admin notice for missing mPDF
     */
    public function mpdf_missing_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php _e('mPDF Library Missing:', 'chatgpt-fluent-connector'); ?></strong>
        <?php _e('To use PDF generation, please install mPDF library.', 'chatgpt-fluent-connector'); ?>
                <a href="https://github.com/mpdf/mpdf" target="_blank"><?php _e('Download mPDF', 'chatgpt-fluent-connector'); ?></a>
            </p>
        </div>
        <?php
    }

    /**
     * Add meta box for PDF settings
     */
    public function add_pdf_settings_meta_box() {
        add_meta_box(
                'sfaic_pdf_settings',
                __('PDF Settings', 'chatgpt-fluent-connector'),
                array($this, 'render_pdf_settings_meta_box'),
                'sfaic_prompt',
                'normal',
                'default'
        );
    }

    /**
     * Render PDF settings meta box
     */
    public function render_pdf_settings_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('sfaic_pdf_settings_save', 'sfaic_pdf_settings_nonce');

        // Get saved values
        $generate_pdf = get_post_meta($post->ID, '_sfaic_generate_pdf', true);
        $pdf_filename = get_post_meta($post->ID, '_sfaic_pdf_filename', true);
        $pdf_attach_to_email = get_post_meta($post->ID, '_sfaic_pdf_attach_to_email', true);

        // Local PDF settings
        $pdf_title = get_post_meta($post->ID, '_sfaic_pdf_title', true);
        $pdf_format = get_post_meta($post->ID, '_sfaic_pdf_format', true);
        $pdf_orientation = get_post_meta($post->ID, '_sfaic_pdf_orientation', true);
        $pdf_margin = get_post_meta($post->ID, '_sfaic_pdf_margin', true);
        $pdf_template_html = get_post_meta($post->ID, '_sfaic_pdf_template_html', true);

        // Set defaults
        if (empty($pdf_filename)) {
            $pdf_filename = 'ai-response-{entry_id}';
        }
        if (empty($pdf_title)) {
            $pdf_title = 'AI Response Report';
        }
        if (empty($pdf_format)) {
            $pdf_format = 'A4';
        }
        if (empty($pdf_orientation)) {
            $pdf_orientation = 'P';
        }
        if (empty($pdf_margin)) {
            $pdf_margin = '15';
        }
        if (empty($pdf_template_html)) {
            $pdf_template_html = $this->get_default_html_template();
        }
        ?>
        <table class="form-table">
            <tr>
                <th><label for="sfaic_generate_pdf"><?php _e('Generate PDF:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="sfaic_generate_pdf" id="sfaic_generate_pdf" value="1" <?php checked($generate_pdf, '1'); ?>>
        <?php _e('Generate PDF from AI response', 'chatgpt-fluent-connector'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the AI response will be converted to PDF using local mPDF library', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_title"><?php _e('PDF Title:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="text" name="sfaic_pdf_title" id="sfaic_pdf_title" value="<?php echo esc_attr($pdf_title); ?>" class="regular-text">
                    <p class="description"><?php _e('Title for the PDF document', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_format"><?php _e('PDF Format:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <select name="sfaic_pdf_format" id="sfaic_pdf_format">
                        <option value="A4" <?php selected($pdf_format, 'A4'); ?>>A4</option>
                        <option value="A3" <?php selected($pdf_format, 'A3'); ?>>A3</option>
                        <option value="A5" <?php selected($pdf_format, 'A5'); ?>>A5</option>
                        <option value="Letter" <?php selected($pdf_format, 'Letter'); ?>>Letter</option>
                        <option value="Legal" <?php selected($pdf_format, 'Legal'); ?>>Legal</option>
                    </select>

                    <select name="sfaic_pdf_orientation" id="sfaic_pdf_orientation" style="margin-left: 10px;">
                        <option value="P" <?php selected($pdf_orientation, 'P'); ?>><?php _e('Portrait', 'chatgpt-fluent-connector'); ?></option>
                        <option value="L" <?php selected($pdf_orientation, 'L'); ?>><?php _e('Landscape', 'chatgpt-fluent-connector'); ?></option>
                    </select>

                    <p class="description"><?php _e('PDF page format and orientation', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_margin"><?php _e('PDF Margin:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="number" name="sfaic_pdf_margin" id="sfaic_pdf_margin" value="<?php echo esc_attr($pdf_margin); ?>" min="0" max="50" step="1"> mm
                    <p class="description"><?php _e('Margin for all sides in millimeters', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_template_html"><?php _e('HTML Template:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <textarea name="sfaic_pdf_template_html" id="sfaic_pdf_template_html" class="large-text code" rows="10"><?php echo esc_textarea($pdf_template_html); ?></textarea>
                    <p class="description">
        <?php _e('HTML template for PDF generation. Available variables:', 'chatgpt-fluent-connector'); ?><br>
                        <code>{title}, {content}, {date}, {time}, {entry_id}, {form_title}</code> <?php _e('+ any form field as', 'chatgpt-fluent-connector'); ?> <code>{field_name}</code>
                    </p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_attach_to_email"><?php _e('Email Attachment:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <label>
                        <input type="checkbox" name="sfaic_pdf_attach_to_email" id="sfaic_pdf_attach_to_email" value="1" <?php checked($pdf_attach_to_email, '1'); ?>>
        <?php _e('Attach PDF to email notifications', 'chatgpt-fluent-connector'); ?>
                    </label>
                    <p class="description"><?php _e('When enabled, the generated PDF will be attached to email notifications', 'chatgpt-fluent-connector'); ?></p>
                </td>
            </tr>

            <tr class="pdf-settings" <?php echo ($generate_pdf != '1') ? 'style="display:none;"' : ''; ?>>
                <th><label for="sfaic_pdf_filename"><?php _e('PDF Filename:', 'chatgpt-fluent-connector'); ?></label></th>
                <td>
                    <input type="text" name="sfaic_pdf_filename" id="sfaic_pdf_filename" value="<?php echo esc_attr($pdf_filename); ?>" class="regular-text">
                    <p class="description">
        <?php _e('Filename for the generated PDF (without .pdf extension).', 'chatgpt-fluent-connector'); ?><br>
        <?php _e('You can use placeholders like {entry_id}, {form_id}, {date}, {time}', 'chatgpt-fluent-connector'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <script>
            jQuery(document).ready(function ($) {
                // Toggle PDF settings visibility
                $('#sfaic_generate_pdf').change(function () {
                    if ($(this).is(':checked')) {
                        $('.pdf-settings').show();
                    } else {
                        $('.pdf-settings').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * Get default HTML template
     */
    private function get_default_html_template() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @font-face {
            font-family: "DejaVu Sans";
            font-style: normal;
            font-weight: normal;
        }
        @font-face {
            font-family: "DejaVu Sans";
            font-style: normal;
            font-weight: bold;
        }
        body { 
            font-family: "DejaVu Sans", Arial, sans-serif; 
            margin: 0; 
            padding: 20px; 
            line-height: 1.6;
            font-size: 14px;
        }
        img {
            max-width: 100%;
            height: auto;
            vertical-align: middle;
        }
        .emoji {
            width: 1.2em;
            height: 1.2em;
            vertical-align: middle;
            display: inline-block;
        }
        .header { 
            background: #f8f9fa; 
            padding: 20px; 
            margin-bottom: 30px; 
            border-bottom: 3px solid #007cba; 
        }
        .title { 
            font-size: 24px; 
            font-weight: bold; 
            color: #333; 
            margin: 0; 
        }
        .meta { 
            color: #666; 
            font-size: 14px; 
            margin-top: 5px; 
        }
        .content { 
            line-height: 1.6; 
            color: #333; 
        }
        .content h1, .content h2, .content h3 {
            color: #0073aa;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        .content ul, .content ol {
            margin-left: 20px;
            margin-bottom: 10px;
        }
        .content li {
            margin-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer { 
            margin-top: 40px; 
            padding-top: 20px; 
            border-top: 1px solid #ddd; 
            font-size: 12px; 
            color: #666; 
        }
        /* Support for status indicators */
        .status-good { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-error { color: #dc3545; }
        /* Support for form rows */
        .form-row {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        .form-row .label {
            font-weight: bold;
            min-width: 150px;
            margin-right: 20px;
        }
        .form-row .content {
            flex: 1;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 class="title">{title}</h1>
        <div class="meta">Generated on {date} at {time} | Entry ID: {entry_id}</div>
    </div>
    
    <div class="content">
        {content}
    </div>
    
    <div class="footer">
        <p>This document was automatically generated from form submission data.</p>
        <p>Form: {form_title}</p>
    </div>
</body>
</html>';
    }

    /**
     * Save PDF settings
     */
    public function save_pdf_settings($post_id, $post) {
        // Check if our custom post type
        if ($post->post_type !== 'sfaic_prompt') {
            return;
        }

        // Check if our nonce is set
        if (!isset($_POST['sfaic_pdf_settings_nonce'])) {
            return;
        }

        // Verify the nonce
        if (!wp_verify_nonce($_POST['sfaic_pdf_settings_nonce'], 'sfaic_pdf_settings_save')) {
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

        // Save the PDF generation option
        $generate_pdf = isset($_POST['sfaic_generate_pdf']) ? '1' : '0';
        update_post_meta($post_id, '_sfaic_generate_pdf', $generate_pdf);

        // Save mPDF settings
        if (isset($_POST['sfaic_pdf_title'])) {
            update_post_meta($post_id, '_sfaic_pdf_title', sanitize_text_field($_POST['sfaic_pdf_title']));
        }

        if (isset($_POST['sfaic_pdf_format'])) {
            update_post_meta($post_id, '_sfaic_pdf_format', sanitize_text_field($_POST['sfaic_pdf_format']));
        }

        if (isset($_POST['sfaic_pdf_orientation'])) {
            update_post_meta($post_id, '_sfaic_pdf_orientation', sanitize_text_field($_POST['sfaic_pdf_orientation']));
        }

        if (isset($_POST['sfaic_pdf_margin'])) {
            update_post_meta($post_id, '_sfaic_pdf_margin', intval($_POST['sfaic_pdf_margin']));
        }

        if (isset($_POST['sfaic_pdf_template_html'])) {
            // Allow HTML but sanitize it
            $allowed_html = wp_kses_allowed_html('post');
            $allowed_html['style'] = array();
            $allowed_html['meta'] = array('charset' => true);
            $html_template = wp_kses($_POST['sfaic_pdf_template_html'], $allowed_html);
            update_post_meta($post_id, '_sfaic_pdf_template_html', $html_template);
        }

        // Save the PDF attach to email option
        $pdf_attach_to_email = isset($_POST['sfaic_pdf_attach_to_email']) ? '1' : '0';
        update_post_meta($post_id, '_sfaic_pdf_attach_to_email', $pdf_attach_to_email);

        // Save PDF filename
        if (isset($_POST['sfaic_pdf_filename'])) {
            update_post_meta($post_id, '_sfaic_pdf_filename', sanitize_text_field($_POST['sfaic_pdf_filename']));
        }
    }

    /**
     * Maybe generate PDF after AI response
     */
    public function maybe_generate_pdf($ai_response, $prompt_id, $entry_id, $form_data, $form) {
        // Check if PDF generation is enabled for this prompt
        $generate_pdf = get_post_meta($prompt_id, '_sfaic_generate_pdf', true);

        if ($generate_pdf != '1') {
            return;
        }

        // Generate PDF with mPDF
        $pdf_result = $this->generate_pdf_with_mpdf($ai_response, $prompt_id, $entry_id, $form_data, $form);

        if (!is_wp_error($pdf_result)) {
            // Store PDF info in entry meta
            update_post_meta($entry_id, '_sfaic_pdf_url', $pdf_result['url']);
            update_post_meta($entry_id, '_sfaic_pdf_filename', $pdf_result['filename']);
            update_post_meta($entry_id, '_sfaic_pdf_path', $pdf_result['path']);
            update_post_meta($entry_id, '_sfaic_pdf_generated_at', current_time('mysql'));
            update_post_meta($entry_id, '_sfaic_pdf_service', 'local_mpdf');
        }
    }

    /**
     * Ensure temp directory exists with proper permissions
     */
    private function ensure_temp_directory() {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/mpdf-temp';

        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
            // Create index.php to prevent directory listing
            file_put_contents($temp_dir . '/index.php', '<?php // Silence is golden');
            // Create .htaccess to deny direct access
            file_put_contents($temp_dir . '/.htaccess', 'deny from all');
        }

        // Ensure directory is writable
        if (!is_writable($temp_dir)) {
            @chmod($temp_dir, 0755);
        }

        return $temp_dir;
    }

    /**
     * Generate PDF with mPDF
     */
    private function generate_pdf_with_mpdf($ai_response, $prompt_id, $entry_id, $form_data, $form) {
        // Check if mPDF is available
        if (!class_exists('Mpdf\Mpdf')) {
            return new WP_Error('mpdf_not_available', __('mPDF library is not available', 'chatgpt-fluent-connector'));
        }

        // Ensure temp directory exists
        $temp_dir = $this->ensure_temp_directory();

        try {
            // Temporarily disable WordPress debug display
            $wp_debug_display = defined('WP_DEBUG_DISPLAY') ? WP_DEBUG_DISPLAY : false;
            if ($wp_debug_display) {
                ini_set('display_errors', 0);
            }

            // Suppress warnings and notices during PDF generation
            $old_error_reporting = error_reporting();
            error_reporting(E_ERROR | E_PARSE);

            // Start output buffering to capture any unwanted output
            ob_start();

            // Clean any existing output
            while (ob_get_level() > 1) {
                ob_end_clean();
            }

            // Get settings
            $pdf_title = get_post_meta($prompt_id, '_sfaic_pdf_title', true);
            $pdf_format = get_post_meta($prompt_id, '_sfaic_pdf_format', true);
            $pdf_orientation = get_post_meta($prompt_id, '_sfaic_pdf_orientation', true);
            $pdf_margin = get_post_meta($prompt_id, '_sfaic_pdf_margin', true);
            $pdf_template_html = get_post_meta($prompt_id, '_sfaic_pdf_template_html', true);
            $pdf_filename = get_post_meta($prompt_id, '_sfaic_pdf_filename', true);

            // Set defaults
            if (empty($pdf_title))
                $pdf_title = 'AI Response Report';
            if (empty($pdf_format))
                $pdf_format = 'A4';
            if (empty($pdf_orientation))
                $pdf_orientation = 'P';
            if (empty($pdf_margin))
                $pdf_margin = 15;
            if (empty($pdf_template_html))
                $pdf_template_html = $this->get_default_html_template();
            if (empty($pdf_filename))
                $pdf_filename = 'ai-response-{entry_id}';

            // Process filename placeholders
            $processed_filename = $this->process_filename_placeholders($pdf_filename, $entry_id, $form_data, $form);

            // Convert emojis to images in the AI response
            $ai_response = $this->convert_emojis_to_images($ai_response);

            // Prepare template variables
            $template_vars = array(
                'title' => $pdf_title,
                'content' => $ai_response,
                'date' => date_i18n(get_option('date_format')),
                'time' => date_i18n(get_option('time_format')),
                'entry_id' => $entry_id,
                'form_title' => $form->title,
                'form_id' => $form->id,
                'datetime' => date_i18n(get_option('date_format') . ' ' . get_option('time_format')),
                'timestamp' => current_time('timestamp'),
                'site_name' => get_bloginfo('name'),
                'site_url' => get_site_url()
            );

            // Add form field data (also convert emojis in form data)
            foreach ($form_data as $field_key => $field_value) {
                if (is_scalar($field_key) && is_scalar($field_value)) {
                    $template_vars[$field_key] = $this->convert_emojis_to_images($field_value);
                } elseif (is_scalar($field_key) && is_array($field_value)) {
                    $template_vars[$field_key] = $this->convert_emojis_to_images(implode(', ', $field_value));
                }
            }

            // Process template
            $html_content = $pdf_template_html;
            foreach ($template_vars as $key => $value) {
                $html_content = str_replace('{' . $key . '}', $value, $html_content);
            }

            // Remove any remaining unprocessed placeholders
            $html_content = preg_replace('/\{[^}]+\}/', '', $html_content);

            // Create mPDF instance with Unicode support
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => $pdf_format,
                'orientation' => $pdf_orientation,
                'margin_left' => $pdf_margin,
                'margin_right' => $pdf_margin,
                'margin_top' => $pdf_margin,
                'margin_bottom' => $pdf_margin,
                'margin_header' => 0,
                'margin_footer' => 0,
                'fontDir' => array_merge((new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'], [
                    SFAIC_DIR . 'fonts/',
                ]),
                'fontdata' => array_merge((new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'], [
                    'dejavusans' => [
                        'R' => 'DejaVuSans.ttf',
                        'B' => 'DejaVuSans-Bold.ttf',
                        'I' => 'DejaVuSans-Oblique.ttf',
                        'BI' => 'DejaVuSans-BoldOblique.ttf',
                        'useOTL' => 0xFF,
                        'useKashida' => 75,
                    ],
                ]),
                'default_font' => 'dejavusans',
                'default_font_size' => 12,
                'autoScriptToLang' => true,
                'autoLangToFont' => true,
                'allow_charset_conversion' => true,
                'charset_in' => 'UTF-8',
                'SetDisplayMode' => 'fullpage',
                'list_indent_first_level' => 0,
                'img_dpi' => 96,
                'allow_output_buffering' => true,
                'tempDir' => $temp_dir,
                'curlAllowUnsafeSslRequests' => true,
                'showImageErrors' => false
            ]);

            // Set document properties
            $mpdf->SetTitle($pdf_title);
            $mpdf->SetAuthor(get_bloginfo('name'));
            $mpdf->SetCreator('AI API Connector Plugin');

            // Write HTML content
            $mpdf->WriteHTML($html_content);

            // Get PDF content as string
            $pdf_content = $mpdf->Output('', 'S');

            // Clean up output buffer and restore error reporting
            ob_end_clean();
            error_reporting($old_error_reporting);

            // Restore debug display
            if ($wp_debug_display) {
                ini_set('display_errors', 1);
            }

            // Save the PDF file
            return $this->save_pdf_data($pdf_content, $processed_filename);
        } catch (Exception $e) {
            // Clean up output buffer and restore error reporting
            ob_end_clean();
            error_reporting($old_error_reporting);

            // Restore debug display
            if (isset($wp_debug_display) && $wp_debug_display) {
                ini_set('display_errors', 1);
            }

            return new WP_Error('mpdf_error', $e->getMessage());
        }
    }

    /**
     * Process filename placeholders
     */
    private function process_filename_placeholders($filename, $entry_id, $form_data, $form) {
        $replacements = array(
            '{entry_id}' => $entry_id,
            '{form_id}' => $form->id,
            '{date}' => date('Y-m-d'),
            '{time}' => date('H-i-s'),
            '{datetime}' => date('Y-m-d_H-i-s'),
        );

        // Add form field data (sanitized for filename)
        foreach ($form_data as $field_key => $field_value) {
            if (is_scalar($field_key) && is_scalar($field_value)) {
                $clean_value = sanitize_file_name($field_value);
                $replacements['{' . $field_key . '}'] = $clean_value;
            }
        }

        $processed = str_replace(array_keys($replacements), array_values($replacements), $filename);
        return sanitize_file_name($processed);
    }

    /**
     * Save PDF data to WordPress uploads
     */
    private function save_pdf_data($pdf_data, $filename) {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/ai-pdfs';
        $pdf_url_dir = $upload_dir['baseurl'] . '/ai-pdfs';

        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);

            // Create .htaccess file to protect PDF directory
            $htaccess_content = "# Protect PDF files\n";
            $htaccess_content .= "<Files *.pdf>\n";
            $htaccess_content .= "    # Allow access to PDFs\n";
            $htaccess_content .= "    Order allow,deny\n";
            $htaccess_content .= "    Allow from all\n";
            $htaccess_content .= "</Files>\n";

            file_put_contents($pdf_dir . '/.htaccess', $htaccess_content);
        }

        // Add .pdf extension if not present
        if (!str_ends_with($filename, '.pdf')) {
            $filename .= '.pdf';
        }

        $file_path = $pdf_dir . '/' . $filename;
        $file_url = $pdf_url_dir . '/' . $filename;

        // Write the PDF data
        $result = file_put_contents($file_path, $pdf_data);

        if ($result === false) {
            return new WP_Error('file_write_failed', 'Failed to write PDF file');
        }

        return array(
            'filename' => $filename,
            'path' => $file_path,
            'url' => $file_url,
            'size' => filesize($file_path)
        );
    }

    /**
     * Get PDF attachment for email
     */
    public function get_pdf_attachment($entry_id) {
        $pdf_path = get_post_meta($entry_id, '_sfaic_pdf_path', true);

        if (!empty($pdf_path) && file_exists($pdf_path)) {
            return $pdf_path;
        }

        return false;
    }

    /**
     * Test local mPDF library
     */
    public function test_mpdf_library() {
        if (!class_exists('Mpdf\Mpdf')) {
            return new WP_Error('mpdf_not_available', __('mPDF library is not installed or not accessible', 'chatgpt-fluent-connector'));
        }

        try {
            // Create a simple test PDF
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'tempDir' => wp_upload_dir()['basedir'] . '/mpdf-temp/'
            ]);

            $test_html = '<h1>Test PDF</h1>';
            $test_html .= '<p>Testing mPDF library with emoji support</p>';
            $test_html .= '<p>Emojis test:</p>';
            $test_html .= '<p>' . $this->convert_emojis_to_images('🌟 ⭐ ✨ ❤️ 💙 💚 ✅ ❌ 🔴 🟢 🔵 💡 🌿') . '</p>';
            $test_html .= '<p>Dutch text: één twee drie</p>';

            $mpdf->WriteHTML($test_html);
            $pdf_content = $mpdf->Output('', 'S');

            if (strlen($pdf_content) > 1000) {
                return array(
                    'service' => 'Local mPDF',
                    'status' => 'success',
                    'message' => 'mPDF library is working correctly! Test PDF generated successfully.',
                    'pdf_size' => strlen($pdf_content) . ' bytes'
                );
            } else {
                return new WP_Error('mpdf_test_failed', __('mPDF test failed - generated content is too small', 'chatgpt-fluent-connector'));
            }
        } catch (Exception $e) {
            return new WP_Error('mpdf_error', __('mPDF error: ', 'chatgpt-fluent-connector') . $e->getMessage());
        }
    }
}
