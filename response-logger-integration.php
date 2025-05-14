<?php
/**
 * Response Logger Integration
 * 
 * This file integrates the fixed response logger with the main plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize the Response Logger integration
 */
function cgptfc_init_response_logger() {
    // Load required files
    require_once plugin_dir_path(__FILE__) . 'includes/class-chatgpt-custom-response-logger.php';
    
    // Hook into the main plugin to replace the response logger
    add_action('plugins_loaded', 'cgptfc_replace_response_logger', 20);
    
    // Make sure the response logs table exists and is up to date
    add_action('admin_init', 'cgptfc_check_logs_table_version');
    
    // Enqueue assets for the logs page
    add_action('admin_enqueue_scripts', 'cgptfc_enqueue_logs_assets');
}

/**
 * Replace the standard response logger with our fixed version
 */
function cgptfc_replace_response_logger() {
    $main = cgptfc_main();
    
    // Only proceed if the main plugin is initialized
    if (!$main) {
        return;
    }
    
    // Create a new instance of the response logger
    $logger = new CGPTFC_Response_Logger();
    
    // Replace the response logger
    $main->response_logger = $logger;
}

/**
 * Check if the response logs table needs to be updated
 */
function cgptfc_check_logs_table_version() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cgptfc_response_logs';
    
    // Get the current table version
    $current_version = get_option('cgptfc_logs_table_version', '1.0');
    
    // If the table version is less than 1.1, we need to update it
    if (version_compare($current_version, '1.1', '<')) {
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name) {
            // Create table if it doesn't exist
            cgptfc_create_logs_table();
        } else {
            // Update existing table
            cgptfc_update_logs_table();
        }
        
        // Update the version option
        update_option('cgptfc_logs_table_version', '1.1');
    }
}

/**
 * Create the response logs table with the updated structure
 */
function cgptfc_create_logs_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'cgptfc_response_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        prompt_id bigint(20) NOT NULL,
        prompt_title varchar(255) DEFAULT NULL,
        form_id bigint(20) NOT NULL,
        entry_id bigint(20) NOT NULL,
        user_prompt longtext NOT NULL,
        ai_response longtext NOT NULL,
        status varchar(50) NOT NULL DEFAULT 'success',
        provider varchar(50) NOT NULL DEFAULT 'openai',
        model varchar(100) DEFAULT NULL,
        execution_time float DEFAULT NULL,
        error_message text DEFAULT NULL,
        created_at datetime NOT NULL,
        PRIMARY KEY  (id),
        KEY prompt_id (prompt_id),
        KEY form_id (form_id),
        KEY entry_id (entry_id),
        KEY status (status),
        KEY created_at (created_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Update the existing response logs table structure
 */
function cgptfc_update_logs_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'cgptfc_response_logs';
    
    // Check if columns already exist to avoid errors
    $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}", ARRAY_A);
    $column_names = array_column($columns, 'Field');
    
    // Add new columns if they don't exist
    if (!in_array('status', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `status` VARCHAR(50) NOT NULL DEFAULT 'success' AFTER `ai_response`");
    }
    
    if (!in_array('provider', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `provider` VARCHAR(50) NOT NULL DEFAULT 'openai' AFTER `status`");
    }
    
    if (!in_array('error_message', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `error_message` TEXT AFTER `provider`");
    }
    
    if (!in_array('prompt_title', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `prompt_title` VARCHAR(255) AFTER `prompt_id`");
    }
    
    if (!in_array('model', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `model` VARCHAR(100) AFTER `provider`");
    }
    
    if (!in_array('execution_time', $column_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD `execution_time` FLOAT AFTER `model`");
    }
    
    // Add missing indexes if needed
    $indexes = $wpdb->get_results("SHOW INDEX FROM {$table_name}", ARRAY_A);
    $index_names = array_column($indexes, 'Key_name');
    
    if (!in_array('status', $index_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX `status` (`status`)");
    }
    
    if (!in_array('created_at', $index_names)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD INDEX `created_at` (`created_at`)");
    }
}

/**
 * Enqueue assets for the response logs page
 */
function cgptfc_enqueue_logs_assets($hook) {
    // Only load on our logs page
    if ($hook !== 'cgptfc_prompt_page_cgptfc-response-logs') {
        return;
    }
    
    // Enqueue CSS
    wp_enqueue_style(
        'cgptfc-logs-styles',
        plugin_dir_url(__FILE__) . 'assets/css/logs-styles.css',
        array(),
        CGPTFC_VERSION
    );
    
    // Enqueue JavaScript
    wp_enqueue_script(
        'cgptfc-logs-script',
        plugin_dir_url(__FILE__) . 'assets/js/logs-script.js',
        array('jquery'),
        CGPTFC_VERSION,
        true
    );
}

// Initialize the response logger integration
cgptfc_init_response_logger();
