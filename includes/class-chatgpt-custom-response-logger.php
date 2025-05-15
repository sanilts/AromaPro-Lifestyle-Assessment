<?php
/**
 * Enhanced Response Logger Class with proper provider handling
 */
class CGPTFC_Response_Logger {
    
    /**
     * Table name
     */
    private $table_name;
    
    /**
     * Table version
     */
    private $table_version = '1.1';
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'cgptfc_response_logs';
        
        // Add admin submenu for logs
        add_action('admin_menu', array($this, 'add_logs_submenu'));
        
        // Make sure table exists
        $this->ensure_table_exists();
        
        // Check if table needs to be updated
        add_action('admin_init', array($this, 'check_table_version'));
        
        // Add assets for the logs page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets($hook) {
        // Only load on our logs page
        if ($hook !== 'cgptfc_prompt_page_cgptfc-response-logs') {
            return;
        }
        
        // Register and enqueue custom CSS
        wp_enqueue_style(
            'cgptfc-logs-styles',
            CGPTFC_URL . 'assets/css/logs-styles.css',
            array(),
            CGPTFC_VERSION
        );
        
        // Register and enqueue custom JS
        wp_enqueue_script(
            'cgptfc-logs-script',
            CGPTFC_URL . 'assets/js/logs-script.js',
            array('jquery'),
            CGPTFC_VERSION,
            true
        );
    }
    
    /**
     * Check if the table structure needs to be updated
     */
    public function check_table_version() {
        $current_version = get_option('cgptfc_logs_table_version', '1.0');
        
        // If the table version is less than our version, we need to update it
        if (version_compare($current_version, $this->table_version, '<')) {
            $this->update_table_structure();
            update_option('cgptfc_logs_table_version', $this->table_version);
        }
    }
    
    /**
     * Update table structure to the latest version
     */
    public function update_table_structure() {
        global $wpdb;
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") !== $this->table_name) {
            // If table doesn't exist, create it with the new structure
            $this->create_logs_table();
            return;
        }
        
        // Check if columns already exist to avoid errors
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$this->table_name}", ARRAY_A);
        $column_names = array_column($columns, 'Field');
        
        // Add new columns if they don't exist
        if (!in_array('status', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `status` VARCHAR(50) NOT NULL DEFAULT 'success' AFTER `ai_response`");
        }
        
        if (!in_array('provider', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `provider` VARCHAR(50) NOT NULL DEFAULT 'openai' AFTER `status`");
        }
        
        if (!in_array('error_message', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `error_message` TEXT AFTER `provider`");
        }
        
        if (!in_array('prompt_title', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `prompt_title` VARCHAR(255) AFTER `prompt_id`");
        }
        
        if (!in_array('model', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `model` VARCHAR(100) AFTER `provider`");
        }
        
        if (!in_array('execution_time', $column_names)) {
            $wpdb->query("ALTER TABLE {$this->table_name} ADD `execution_time` FLOAT AFTER `model`");
        }
    }
    
    /**
     * Make sure the response logs table exists
     */
    private function ensure_table_exists() {
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        if (!$table_exists) {
            $this->create_logs_table();
        }
    }
    
    /**
     * Add logs submenu
     */
    public function add_logs_submenu() {
        add_submenu_page(
            'edit.php?post_type=cgptfc_prompt', 
            __('Response Logs', 'chatgpt-fluent-connector'),
            __('Response Logs', 'chatgpt-fluent-connector'),
            'manage_options',
            'cgptfc-response-logs',
            array($this, 'render_logs_page')
        );
    }
    
    /**
     * Create logs table with enhanced fields
     */
    public function create_logs_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
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
        
        // Set the table version
        update_option('cgptfc_logs_table_version', $this->table_version);
    }
    
    /**
     * Log a response with enhanced details and proper provider tracking
     * 
     * @param int $prompt_id The prompt ID
     * @param int $entry_id The submission entry ID
     * @param int $form_id The form ID
     * @param string $user_prompt The user prompt (template with placeholders)
     * @param string|WP_Error $ai_response The AI response or error
     * @param string $provider The API provider (openai or gemini)
     * @param string $model The model used
     * @param float $execution_time The execution time in seconds
     * @return bool|int The row ID or false on failure
     */
    public function log_response($prompt_id, $entry_id, $form_id, $user_prompt, $ai_response, $provider = null, $model = '', $execution_time = null) {
        global $wpdb;
        $debug_mode = get_option('cgptfc_debug_mode', '0');
        
        // Ensure table exists before trying to insert
        $this->ensure_table_exists();
        
        // Check for WP_Error
        $status = 'success';
        $error_message = '';
        $response_text = '';
        
        if (is_wp_error($ai_response)) {
            $status = 'error';
            $error_message = $ai_response->get_error_message();
            $response_text = ''; // Empty response for errors
        } else {
            $response_text = $ai_response;
        }
        
        // Get prompt title
        $prompt_title = get_the_title($prompt_id);
        
        // If provider is not specified, get it from the system setting
        if (empty($provider)) {
            $provider = get_option('cgptfc_api_provider', 'openai');
        }
        
        if ($debug_mode === '1') {
            error_log('CGPTFC: Logging response with provider: ' . $provider);
        }
        
        // Check if model is missing - use default values based on provider
        if (empty($model)) {
            if ($provider === 'gemini') {
                $model = get_option('cgptfc_gemini_model', 'gemini-pro');
            } else {
                $model = get_option('cgptfc_model', 'gpt-3.5-turbo');
            }
        }
        
        // Prepare data with proper types
        $data = array(
            'prompt_id' => $prompt_id,
            'prompt_title' => $prompt_title,
            'form_id' => $form_id,
            'entry_id' => $entry_id,
            'user_prompt' => $user_prompt,
            'ai_response' => $response_text,
            'status' => $status,
            'provider' => $provider,
            'model' => $model,
            'error_message' => $error_message,
            'created_at' => current_time('mysql')
        );
        
        // Add execution_time only if it's provided
        if ($execution_time !== null) {
            $data['execution_time'] = $execution_time;
        }
        
        // Insert the log
        $result = $wpdb->insert(
            $this->table_name,
            $data,
            array(
                '%d', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s'
            )
        );
        
        if ($result === false) {
            // Log error for debugging
            error_log('CGPTFC: Failed to log response. WP Database Error: ' . $wpdb->last_error);
            return false;
        }
        
        if ($debug_mode === '1') {
            error_log('CGPTFC: Successfully logged response with ID: ' . $wpdb->insert_id . ', provider: ' . $provider);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs by prompt ID with enhanced fields
     * 
     * @param int $prompt_id The prompt ID
     * @param int $limit Optional. Number of logs to retrieve.
     * @param int $offset Optional. Offset for pagination.
     * @return array The logs
     */
    public function get_logs_by_prompt($prompt_id, $limit = 10, $offset = 0) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE prompt_id = %d 
            ORDER BY created_at DESC 
            LIMIT %d OFFSET %d",
            $prompt_id, $limit, $offset
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Count logs by prompt ID
     * 
     * @param int $prompt_id The prompt ID
     * @return int The count
     */
    public function count_logs_by_prompt($prompt_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} WHERE prompt_id = %d",
            $prompt_id
        );
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Get all logs with provider filtering capability
     * 
     * @param array $filters Optional. Array of filters.
     * @param int $limit Optional. Number of logs to retrieve.
     * @param int $offset Optional. Offset for pagination.
     * @return array The logs
     */
    public function get_all_logs($filters = array(), $limit = 20, $offset = 0) {
        global $wpdb;
        
        $where_clauses = array();
        $query_params = array();
        
        // Process filters
        if (!empty($filters['prompt_id'])) {
            $where_clauses[] = 'l.prompt_id = %d';
            $query_params[] = $filters['prompt_id'];
        }
        
        if (!empty($filters['form_id'])) {
            $where_clauses[] = 'l.form_id = %d';
            $query_params[] = $filters['form_id'];
        }
        
        if (!empty($filters['entry_id'])) {
            $where_clauses[] = 'l.entry_id = %d';
            $query_params[] = $filters['entry_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'l.status = %s';
            $query_params[] = $filters['status'];
        }
        
        if (!empty($filters['provider'])) {
            $where_clauses[] = 'l.provider = %s';
            $query_params[] = $filters['provider'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'l.created_at >= %s';
            $query_params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'l.created_at <= %s';
            $query_params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Build the WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        // Add limit and offset
        $query_params[] = $limit;
        $query_params[] = $offset;
        
        $sql = "SELECT l.*, p.post_title as prompt_title 
            FROM {$this->table_name} l
            LEFT JOIN {$wpdb->posts} p ON l.prompt_id = p.ID
            $where_sql
            ORDER BY l.created_at DESC 
            LIMIT %d OFFSET %d";
        
        $prepared_sql = $wpdb->prepare($sql, $query_params);
        
        return $wpdb->get_results($prepared_sql);
    }
    
    /**
     * Count all logs with filters
     * 
     * @param array $filters Optional. Array of filters.
     * @return int The count
     */
    public function count_all_logs($filters = array()) {
        global $wpdb;
        
        $where_clauses = array();
        $query_params = array();
        
        // Process filters - same as get_all_logs
        if (!empty($filters['prompt_id'])) {
            $where_clauses[] = 'prompt_id = %d';
            $query_params[] = $filters['prompt_id'];
        }
        
        if (!empty($filters['form_id'])) {
            $where_clauses[] = 'form_id = %d';
            $query_params[] = $filters['form_id'];
        }
        
        if (!empty($filters['entry_id'])) {
            $where_clauses[] = 'entry_id = %d';
            $query_params[] = $filters['entry_id'];
        }
        
        if (!empty($filters['status'])) {
            $where_clauses[] = 'status = %s';
            $query_params[] = $filters['status'];
        }
        
        if (!empty($filters['provider'])) {
            $where_clauses[] = 'provider = %s';
            $query_params[] = $filters['provider'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_clauses[] = 'created_at >= %s';
            $query_params[] = $filters['date_from'] . ' 00:00:00';
        }
        
        if (!empty($filters['date_to'])) {
            $where_clauses[] = 'created_at <= %s';
            $query_params[] = $filters['date_to'] . ' 23:59:59';
        }
        
        // Build the WHERE clause
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table_name} $where_sql";
        
        if (!empty($query_params)) {
            $sql = $wpdb->prepare($sql, $query_params);
        }
        
        return (int)$wpdb->get_var($sql);
    }
    
    /**
     * Delete logs by prompt ID
     * 
     * @param int $prompt_id The prompt ID
     * @return int|false The number of rows deleted, or false on error
     */
    public function delete_logs_by_prompt($prompt_id) {
        global $wpdb;
        
        return $wpdb->delete(
            $this->table_name,
            array('prompt_id' => $prompt_id),
            array('%d')
        );
    }
    
    /**
     * Get recent log count by provider
     * 
     * @param string $provider The provider name
     * @param int $days Number of days to look back 
     * @return int Count of logs
     */
    public function get_provider_log_count($provider, $days = 30) {
        global $wpdb;
        
        $date_limit = date('Y-m-d H:i:s', strtotime("-$days days"));
        
        $sql = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name} 
            WHERE provider = %s AND created_at >= %s",
            $provider, $date_limit
        );
        
        return (int)$wpdb->get_var($sql);
    }
    
    /**
     * Render log details view with proper provider identification
     */
    private function render_log_details($log_id) {
        global $wpdb;
        
        // Get the log entry
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE id = %d",
            $log_id
        ));
        
        if (!$log) {
            wp_die(__('Log entry not found.', 'chatgpt-fluent-connector'));
        }
        
        // Get form name
        $form_name = '';
        if (function_exists('wpFluent')) {
            $form = wpFluent()->table('fluentform_forms')
                    ->select('title')
                    ->where('id', $log->form_id)
                    ->first();
            
            if ($form) {
                $form_name = $form->title;
            }
        }
        
        // Get entry data if available
        $entry_data = null;
        if (function_exists('wpFluent')) {
            $entry = wpFluent()->table('fluentform_submissions')
                    ->where('form_id', $log->form_id)
                    ->where('id', $log->entry_id)
                    ->first();
            
            if ($entry && !empty($entry->response)) {
                $entry_data = json_decode($entry->response, true);
            }
        }
        
        // Format execution time
        $execution_time = isset($log->execution_time) ? round($log->execution_time, 2) . 's' : '-';
        
        // Prepare status badge
        $status_badge = ($log->status === 'error') 
            ? '<span class="cgptfc-badge cgptfc-badge-error">' . __('Error', 'chatgpt-fluent-connector') . '</span>' 
            : '<span class="cgptfc-badge cgptfc-badge-success">' . __('Success', 'chatgpt-fluent-connector') . '</span>';
        
        // Prepare provider badge
        $provider_badge = '';
        if ($log->provider === 'openai') {
            $provider_badge = '<span class="cgptfc-api-badge openai">ChatGPT</span>';
        } elseif ($log->provider === 'gemini') {
            $provider_badge = '<span class="cgptfc-api-badge gemini">Gemini</span>';
        }
        
        // Render the page
        ?>
        <div class="wrap">
            <h1>
                <?php _e('Log Details', 'chatgpt-fluent-connector'); ?>
                <a href="<?php echo esc_url(admin_url('edit.php?post_type=cgptfc_prompt&page=cgptfc-response-logs')); ?>" class="page-title-action">
                    <?php _e('Back to Logs', 'chatgpt-fluent-connector'); ?>
                </a>
            </h1>
            
            <div class="metabox-holder">
                <!-- Main Info -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Log Information', 'chatgpt-fluent-connector'); ?></span></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Log ID:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo esc_html($log->id); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Date:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo $status_badge; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Execution Time:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo esc_html($execution_time); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Prompt:', 'chatgpt-fluent-connector'); ?></th>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($log->prompt_id)); ?>">
                                        <?php echo esc_html(get_the_title($log->prompt_id)); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Form:', 'chatgpt-fluent-connector'); ?></th>
                                <td>
                                    <?php 
                                        if (!empty($form_name)) {
                                            echo esc_html($form_name) . ' (ID: ' . esc_html($log->form_id) . ')';
                                        } else {
                                            echo esc_html(__('Form ID:', 'chatgpt-fluent-connector') . ' ' . $log->form_id);
                                        }
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Entry ID:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo esc_html($log->entry_id); ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Provider:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo $provider_badge; ?></td>
                            </tr>
                            <tr>
                                <th><?php _e('Model:', 'chatgpt-fluent-connector'); ?></th>
                                <td><?php echo esc_html($log->model); ?></td>
                            </tr>
                            <?php if ($log->status === 'error' && !empty($log->error_message)) : ?>
                                <tr>
                                    <th><?php _e('Error:', 'chatgpt-fluent-connector'); ?></th>
                                    <td>
                                        <div class="cgptfc-error-message">
                                            <?php echo esc_html($log->error_message); ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <!-- User Prompt -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('User Prompt', 'chatgpt-fluent-connector'); ?></span></h2>
                    <div class="inside">
                        <div class="cgptfc-content-box">
                            <?php echo nl2br(esc_html($log->user_prompt)); ?>
                        </div>
                    </div>
                </div>
                
                <!-- AI Response -->
                <div class="postbox">
                    <h2 class="hndle"><span><?php 
                        $provider_name = ($log->provider === 'gemini') ? __('Google Gemini', 'chatgpt-fluent-connector') : __('ChatGPT', 'chatgpt-fluent-connector');
                        echo sprintf(__('%s Response', 'chatgpt-fluent-connector'), $provider_name); 
                    ?></span></h2>
                    <div class="inside">
                        <?php if ($log->status === 'success' && !empty($log->ai_response)) : ?>
                            <div class="cgptfc-response-tabs">
                                <a href="#" class="cgptfc-view-toggle active" data-target="cgptfc-raw-response"><?php _e('Raw Response', 'chatgpt-fluent-connector'); ?></a>
                                <a href="#" class="cgptfc-view-toggle" data-target="cgptfc-rendered-response"><?php _e('Rendered Response', 'chatgpt-fluent-connector'); ?></a>
                                <a href="#" class="button button-small cgptfc-copy-response" style="float:right;"><?php _e('Copy to Clipboard', 'chatgpt-fluent-connector'); ?></a>
                            </div>
                            
                            <div id="cgptfc-raw-response" class="cgptfc-response-view">
                                <pre class="cgptfc-code-block"><?php echo esc_html($log->ai_response); ?></pre>
                            </div>
                            
                            <div id="cgptfc-rendered-response" class="cgptfc-response-view" style="display:none;">
                                <div class="cgptfc-rendered-response html-rendered">
                                    <?php echo wp_kses_post(nl2br($log->ai_response)); ?>
                                </div>
                            </div>
                        <?php elseif ($log->status === 'error') : ?>
                            <div class="notice notice-error inline">
                                <p><?php _e('The AI request failed.', 'chatgpt-fluent-connector'); ?></p>
                                <?php if (!empty($log->error_message)) : ?>
                                    <p><strong><?php _e('Error:', 'chatgpt-fluent-connector'); ?></strong> <?php echo esc_html($log->error_message); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php else : ?>
                            <div class="notice notice-warning inline">
                                <p><?php _e('No response content available.', 'chatgpt-fluent-connector'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Form Data -->
                <?php if ($entry_data && is_array($entry_data)) : ?>
                <div class="postbox">
                    <h2 class="hndle"><span><?php _e('Form Submission Data', 'chatgpt-fluent-connector'); ?></span></h2>
                    <div class="inside">
                        <table class="widefat striped">
                            <thead>
                                <tr>
                                    <th><?php _e('Field', 'chatgpt-fluent-connector'); ?></th>
                                    <th><?php _e('Value', 'chatgpt-fluent-connector'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entry_data as $field_key => $field_value) : 
                                    // Skip internal fields
                                    if (!is_scalar($field_key) || strpos($field_key, '_') === 0) {
                                        continue;
                                    }
                                    
                                    // Format array values
                                    if (is_array($field_value)) {
                                        $field_value = implode(', ', $field_value);
                                    } elseif (!is_scalar($field_value)) {
                                        // Skip non-scalar values
                                        continue;
                                    }
                                ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($field_key); ?></strong></td>
                                        <td><?php echo esc_html($field_value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render logs page with enhanced provider filtering
     */
    public function render_logs_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chatgpt-fluent-connector'));
        }
        
        global $wpdb;
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        
        // Process view log details action
        if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['log_id'])) {
            $this->render_log_details((int)$_GET['log_id']);
            return;
        }
        
        // Get filters
        $filters = array();
        
        if (isset($_GET['prompt_id']) && !empty($_GET['prompt_id'])) {
            $filters['prompt_id'] = (int)$_GET['prompt_id'];
        }
        
        if (isset($_GET['form_id']) && !empty($_GET['form_id'])) {
            $filters['form_id'] = (int)$_GET['form_id'];
        }
        
        if (isset($_GET['status']) && in_array($_GET['status'], array('success', 'error'))) {
            $filters['status'] = sanitize_text_field($_GET['status']);
        }
        
        if (isset($_GET['provider']) && in_array($_GET['provider'], array('openai', 'gemini'))) {
            $filters['provider'] = sanitize_text_field($_GET['provider']);
        }
        
        if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_GET['date_from']);
        }
        
        if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_GET['date_to']);
        }
        
        // Get current page and items per page
        $page = isset($_GET['paged']) ? max(1, (int)$_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Get logs with filters
        $logs = array();
        $total_logs = 0;
        
        if ($table_exists) {
            $logs = $this->get_all_logs($filters, $per_page, $offset);
            $total_logs = $this->count_all_logs($filters);
        }
        
        // Get prompts for filter dropdown
        $prompts = get_posts(array(
            'post_type' => 'cgptfc_prompt',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Calculate pagination
        $total_pages = ceil($total_logs / $per_page);
        
        // Provider stats
        $openai_count = $this->get_provider_log_count('openai', 30);
        $gemini_count = $this->get_provider_log_count('gemini', 30);
        
        ?>
        <div class="wrap">
            <h1><?php _e('AI Response Logs', 'chatgpt-fluent-connector'); ?></h1>
            
            <!-- Provider stats -->
            <div class="provider-stats" style="margin-bottom: 15px; background-color: #fff; padding: 10px; border: 1px solid #ccd0d4; border-radius: 3px;">
                <h3><?php _e('API Usage in Last 30 Days', 'chatgpt-fluent-connector'); ?></h3>
                <div style="display: flex; gap: 20px;">
                    <div>
                        <span class="cgptfc-api-badge openai">ChatGPT</span>
                        <strong><?php echo number_format_i18n($openai_count); ?></strong> <?php _e('requests', 'chatgpt-fluent-connector'); ?>
                    </div>
                    <div>
                        <span class="cgptfc-api-badge gemini">Gemini</span>
                        <strong><?php echo number_format_i18n($gemini_count); ?></strong> <?php _e('requests', 'chatgpt-fluent-connector'); ?>
                    </div>
                </div>
            </div>
            
            <?php if (!$table_exists): ?>
                <div class="notice notice-error">
                    <p><?php _e('The logs table does not exist. Please try reactivating the plugin to create it.', 'chatgpt-fluent-connector'); ?></p>
                </div>
            <?php elseif (empty($logs) && empty($filters)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No logs found. This could be because no forms have been submitted yet, or logging is not enabled on your prompts.', 'chatgpt-fluent-connector'); ?></p>
                    <p><?php _e('To enable logging, edit a prompt and check the "Save responses to the database" option under Response Handling.', 'chatgpt-fluent-connector'); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Enhanced Filters with Provider Selection -->
            <div class="tablenav top">
                <form method="get" class="alignleft actions">
                    <input type="hidden" name="post_type" value="cgptfc_prompt">
                    <input type="hidden" name="page" value="cgptfc-response-logs">
                    
                    <div class="alignleft actions" style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 10px;">
                        <!-- Prompt filter -->
                        <select name="prompt_id">
                            <option value=""><?php _e('All Prompts', 'chatgpt-fluent-connector'); ?></option>
                            <?php foreach ($prompts as $prompt) : ?>
                                <option value="<?php echo esc_attr($prompt->ID); ?>" <?php selected(isset($filters['prompt_id']) ? $filters['prompt_id'] : '', $prompt->ID); ?>>
                                    <?php echo esc_html($prompt->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Provider filter -->
                        <select name="provider">
                            <option value=""><?php _e('All Providers', 'chatgpt-fluent-connector'); ?></option>
                            <option value="openai" <?php selected(isset($filters['provider']) ? $filters['provider'] : '', 'openai'); ?>><?php _e('OpenAI (ChatGPT)', 'chatgpt-fluent-connector'); ?></option>
                            <option value="gemini" <?php selected(isset($filters['provider']) ? $filters['provider'] : '', 'gemini'); ?>><?php _e('Google Gemini', 'chatgpt-fluent-connector'); ?></option>
                        </select>
                        
                        <!-- Status filter -->
                        <select name="status">
                            <option value=""><?php _e('All Statuses', 'chatgpt-fluent-connector'); ?></option>
                            <option value="success" <?php selected(isset($filters['status']) ? $filters['status'] : '', 'success'); ?>><?php _e('Success', 'chatgpt-fluent-connector'); ?></option>
                            <option value="error" <?php selected(isset($filters['status']) ? $filters['status'] : '', 'error'); ?>><?php _e('Error', 'chatgpt-fluent-connector'); ?></option>
                        </select>
                        
                        <!-- Date filters -->
                        <span>
                            <input type="date" name="date_from" placeholder="<?php _e('From date', 'chatgpt-fluent-connector'); ?>" 
                                value="<?php echo isset($filters['date_from']) ? esc_attr($filters['date_from']) : ''; ?>">
                        </span>
                        <span>
                            <input type="date" name="date_to" placeholder="<?php _e('To date', 'chatgpt-fluent-connector'); ?>"
                                value="<?php echo isset($filters['date_to']) ? esc_attr($filters['date_to']) : ''; ?>">
                        </span>
                        
                        <input type="submit" class="button" value="<?php _e('Filter', 'chatgpt-fluent-connector'); ?>">
                        
                        <?php if (!empty($filters)): ?>
                            <a href="<?php echo esc_url(admin_url('edit.php?post_type=cgptfc_prompt&page=cgptfc-response-logs')); ?>" class="button">
                                <?php _e('Reset Filters', 'chatgpt-fluent-connector'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
                
                <!-- Pagination -->
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : 
                        // Build pagination URL base
                        $pagination_url_args = array(
                            'post_type' => 'cgptfc_prompt',
                            'page' => 'cgptfc-response-logs'
                        );
                        
                        // Add filters to pagination URLs
                        foreach ($filters as $key => $value) {
                            $pagination_url_args[$key] = $value;
                        }
                        
                        // Create pagination HTML
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', admin_url('edit.php?' . http_build_query($pagination_url_args))),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                    endif; ?>
                </div>
            </div>
            
            <!-- Logs Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 60px;"><?php _e('ID', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 180px;"><?php _e('Date', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Prompt', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 80px;"><?php _e('Form ID', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 80px;"><?php _e('Entry ID', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 90px;"><?php _e('Status', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 100px;"><?php _e('Provider', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 120px;"><?php _e('Model', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 80px;"><?php _e('Time (s)', 'chatgpt-fluent-connector'); ?></th>
                        <th style="width: 120px;"><?php _e('Actions', 'chatgpt-fluent-connector'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)) : ?>
                        <?php foreach ($logs as $log) : 
                            // Determine row class based on status
                            $row_class = ($log->status === 'error') ? 'error' : '';
                            
                            // Format execution time
                            $execution_time = isset($log->execution_time) ? round($log->execution_time, 2) . 's' : '-';
                            
                            // Prepare status badge
                            $status_badge = ($log->status === 'error') 
                                ? '<span class="cgptfc-badge cgptfc-badge-error">' . __('Error', 'chatgpt-fluent-connector') . '</span>' 
                                : '<span class="cgptfc-badge cgptfc-badge-success">' . __('Success', 'chatgpt-fluent-connector') . '</span>';
                            
                            // Prepare provider badge
                            $provider_badge = '';
                            if ($log->provider === 'openai') {
                                $provider_badge = '<span class="cgptfc-api-badge openai">ChatGPT</span>';
                            } elseif ($log->provider === 'gemini') {
                                $provider_badge = '<span class="cgptfc-api-badge gemini">Gemini</span>';
                            }
                        ?>
                            <tr class="<?php echo esc_attr($row_class); ?>">
                                <td><?php echo esc_html($log->id); ?></td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                                </td>
                                <td>
                                    <?php if (isset($log->prompt_title) && !empty($log->prompt_title)) : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($log->prompt_id)); ?>">
                                            <?php echo esc_html($log->prompt_title); ?>
                                        </a>
                                    <?php else : ?>
                                        <a href="<?php echo esc_url(get_edit_post_link($log->prompt_id)); ?>">
                                            <?php echo esc_html(get_the_title($log->prompt_id)); ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($log->form_id); ?></td>
                                <td><?php echo esc_html($log->entry_id); ?></td>
                                <td><?php echo $status_badge; ?></td>
                                <td><?php echo $provider_badge; ?></td>
                                <td><?php echo esc_html($log->model); ?></td>
                                <td><?php echo esc_html($execution_time); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(add_query_arg(array(
                                        'post_type' => 'cgptfc_prompt',
                                        'page' => 'cgptfc-response-logs',
                                        'action' => 'view',
                                        'log_id' => $log->id
                                    ))); ?>" class="button button-small">
                                        <?php _e('View Details', 'chatgpt-fluent-connector'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10"><?php _e('No logs found matching your criteria.', 'chatgpt-fluent-connector'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Bottom Pagination (same as top) -->
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : 
                        // Build pagination URL base (same as above)
                        $pagination_url_args = array(
                            'post_type' => 'cgptfc_prompt',
                            'page' => 'cgptfc-response-logs'
                        );
                        
                        // Add filters to pagination URLs
                        foreach ($filters as $key => $value) {
                            $pagination_url_args[$key] = $value;
                        }
                        
                        // Create pagination HTML (same as above)
                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', admin_url('edit.php?' . http_build_query($pagination_url_args))),
                            'format' => '',
                            'prev_text' => __('&laquo;'),
                            'next_text' => __('&raquo;'),
                            'total' => $total_pages,
                            'current' => $page
                        ));
                    endif; ?>
                </div>
            </div>
        </div>
        <?php
    }
}