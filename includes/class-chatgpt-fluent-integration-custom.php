<?php
/**
 * ChatGPT Response Logger Class - With Fixed Logs Display
 * 
 * Handles logging and retrieval of ChatGPT responses
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CGPTFC_Response_Logger {
    
    /**
     * Table name
     */
    private $table_name;
    
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
     * Create logs table
     */
    public function create_logs_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            prompt_id bigint(20) NOT NULL,
            form_id bigint(20) NOT NULL,
            entry_id bigint(20) NOT NULL,
            user_prompt longtext NOT NULL,
            ai_response longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Log table creation for debugging
        error_log('ChatGPT Response Logger: Table creation attempted. Result: ' . ($wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name ? 'Success' : 'Failed - ' . $wpdb->last_error));
    }
    
    /**
     * Log a response
     * 
     * @param int $prompt_id The prompt ID
     * @param int $entry_id The submission entry ID
     * @param int $form_id The form ID
     * @param string $user_prompt The user prompt (template with placeholders)
     * @param string $ai_response The AI response
     * @return bool|int The row ID or false on failure
     */
    public function log_response($prompt_id, $entry_id, $form_id, $user_prompt, $ai_response) {
        global $wpdb;
        
        // Ensure table exists before trying to insert
        $this->ensure_table_exists();
        
        // Debug log
        error_log("ChatGPT Logger: Attempting to log response for prompt ID: $prompt_id, entry ID: $entry_id");
        
        // Insert the log
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'prompt_id' => $prompt_id,
                'form_id' => $form_id,
                'entry_id' => $entry_id,
                'user_prompt' => $user_prompt,
                'ai_response' => $ai_response,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        // Log any errors for debugging
        if ($result === false) {
            error_log('ChatGPT Logger Error: Failed to insert response log - ' . $wpdb->last_error);
            return false;
        }
        
        error_log('ChatGPT Logger: Response logged successfully. ID: ' . $wpdb->insert_id);
        return $wpdb->insert_id;
    }
    
    /**
     * Get logs by prompt ID
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
     * Get all logs
     * 
     * @param int $limit Optional. Number of logs to retrieve.
     * @param int $offset Optional. Offset for pagination.
     * @return array The logs
     */
    public function get_all_logs($limit = 20, $offset = 0) {
        global $wpdb;
        
        $sql = $wpdb->prepare(
            "SELECT l.*, p.post_title as prompt_title 
            FROM {$this->table_name} l
            LEFT JOIN {$wpdb->posts} p ON l.prompt_id = p.ID
            ORDER BY l.created_at DESC 
            LIMIT %d OFFSET %d",
            $limit, $offset
        );
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Count all logs
     * 
     * @return int The count
     */
    public function count_all_logs() {
        global $wpdb;
        
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
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
     * Render logs page - Fixed version with debugging info
     */
    public function render_logs_page() {
        // Check user capability
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'chatgpt-fluent-connector'));
        }
        
        global $wpdb;
        
        // Show debug information
        echo '<div class="notice notice-info is-dismissible"><p>';
        echo '<strong>Debug Info:</strong><br>';
        echo 'Table name: ' . esc_html($this->table_name) . '<br>';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
        echo 'Table exists: ' . ($table_exists ? 'Yes' : 'No') . '<br>';
        
        // If table exists, show column info
        if ($table_exists) {
            $columns = $wpdb->get_results("DESCRIBE {$this->table_name}");
            echo 'Table columns: ';
            foreach ($columns as $column) {
                echo esc_html($column->Field) . ' (' . esc_html($column->Type) . '), ';
            }
            echo '<br>';
            
            // Count records
            $count = $this->count_all_logs();
            echo 'Total records: ' . esc_html($count) . '<br>';
            
            // Show last query for debugging
            echo 'Last SQL query: ' . esc_html($wpdb->last_query) . '<br>';
            
            // Show any SQL errors
            if (!empty($wpdb->last_error)) {
                echo 'Last SQL error: ' . esc_html($wpdb->last_error) . '<br>';
            }
        } else {
            // Try to create the table
            echo 'Attempting to create table...<br>';
            $this->create_logs_table();
            
            // Check again if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
            echo 'Table creation result: ' . ($table_exists ? 'Success' : 'Failed - ' . esc_html($wpdb->last_error)) . '<br>';
        }
        echo '</p></div>';
        
        // Get current page and items per page
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        // Get prompt filter
        $prompt_filter = isset($_GET['prompt_id']) ? absint($_GET['prompt_id']) : 0;
        
        // Get logs
        $logs = array();
        $total_logs = 0;
        
        if ($table_exists) {
            if ($prompt_filter) {
                $logs = $this->get_logs_by_prompt($prompt_filter, $per_page, $offset);
                $total_logs = $this->count_logs_by_prompt($prompt_filter);
            } else {
                $logs = $this->get_all_logs($per_page, $offset);
                $total_logs = $this->count_all_logs();
            }
        }
        
        // Get all prompts for the filter dropdown
        $prompts = get_posts(array(
            'post_type' => 'cgptfc_prompt',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));
        
        // Calculate pagination
        $total_pages = ceil($total_logs / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php _e('ChatGPT Response Logs', 'chatgpt-fluent-connector'); ?></h1>
            
            <?php if (!$table_exists): ?>
                <div class="notice notice-error">
                    <p><?php _e('The logs table does not exist. Please try reactivating the plugin to create it.', 'chatgpt-fluent-connector'); ?></p>
                </div>
            <?php elseif (empty($logs)): ?>
                <div class="notice notice-info">
                    <p><?php _e('No logs found. This could be because no forms have been submitted yet, or logging is not enabled on your prompts.', 'chatgpt-fluent-connector'); ?></p>
                    <p><?php _e('To enable logging, edit a prompt and check the "Save responses to the database" option under Response Handling.', 'chatgpt-fluent-connector'); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="post_type" value="cgptfc_prompt">
                        <input type="hidden" name="page" value="cgptfc-response-logs">
                        <select name="prompt_id">
                            <option value="0"><?php _e('All Prompts', 'chatgpt-fluent-connector'); ?></option>
                            <?php foreach ($prompts as $prompt) : ?>
                                <option value="<?php echo esc_attr($prompt->ID); ?>" <?php selected($prompt_filter, $prompt->ID); ?>>
                                    <?php echo esc_html($prompt->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="submit" class="button" value="<?php _e('Filter', 'chatgpt-fluent-connector'); ?>">
                    </form>
                </div>
                
                <div class="tablenav-pages">
                    <?php if ($total_pages > 1) : ?>
                        <span class="displaying-num">
                            <?php printf(_n('%s item', '%s items', $total_logs, 'chatgpt-fluent-connector'), number_format_i18n($total_logs)); ?>
                        </span>
                        
                        <span class="pagination-links">
                            <?php
                            // First page link
                            if ($page > 1) {
                                printf('<a class="first-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">«</span></a>',
                                    esc_url(add_query_arg(array('paged' => 1, 'prompt_id' => $prompt_filter))),
                                    __('First page', 'chatgpt-fluent-connector')
                                );
                            } else {
                                echo '<span class="first-page button disabled"><span class="screen-reader-text">' . __('First page', 'chatgpt-fluent-connector') . '</span><span aria-hidden="true">«</span></span>';
                            }
                            
                            // Previous page link
                            if ($page > 1) {
                                printf('<a class="prev-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">‹</span></a>',
                                    esc_url(add_query_arg(array('paged' => max(1, $page - 1), 'prompt_id' => $prompt_filter))),
                                    __('Previous page', 'chatgpt-fluent-connector')
                                );
                            } else {
                                echo '<span class="prev-page button disabled"><span class="screen-reader-text">' . __('Previous page', 'chatgpt-fluent-connector') . '</span><span aria-hidden="true">‹</span></span>';
                            }
                            
                            // Current of total pages
                            printf('<span class="paging-input"><span class="tablenav-paging-text">%s</span></span>',
                                sprintf(__('%1$s of %2$s', 'chatgpt-fluent-connector'), number_format_i18n($page), number_format_i18n($total_pages))
                            );
                            
                            // Next page link
                            if ($page < $total_pages) {
                                printf('<a class="next-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">›</span></a>',
                                    esc_url(add_query_arg(array('paged' => min($total_pages, $page + 1), 'prompt_id' => $prompt_filter))),
                                    __('Next page', 'chatgpt-fluent-connector')
                                );
                            } else {
                                echo '<span class="next-page button disabled"><span class="screen-reader-text">' . __('Next page', 'chatgpt-fluent-connector') . '</span><span aria-hidden="true">›</span></span>';
                            }
                            
                            // Last page link
                            if ($page < $total_pages) {
                                printf('<a class="last-page button" href="%s"><span class="screen-reader-text">%s</span><span aria-hidden="true">»</span></a>',
                                    esc_url(add_query_arg(array('paged' => $total_pages, 'prompt_id' => $prompt_filter))),
                                    __('Last page', 'chatgpt-fluent-connector')
                                );
                            } else {
                                echo '<span class="last-page button disabled"><span class="screen-reader-text">' . __('Last page', 'chatgpt-fluent-connector') . '</span><span aria-hidden="true">»</span></span>';
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('ID', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Prompt', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Form ID', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Entry ID', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Response', 'chatgpt-fluent-connector'); ?></th>
                        <th><?php _e('Date', 'chatgpt-fluent-connector'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)) : ?>
                        <?php foreach ($logs as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log->id); ?></td>
                                <td>
                                    <?php if (isset($log->prompt_title)) : ?>
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
                                <td>
                                    <div style="max-height: 100px; overflow-y: auto; font-size: 12px; white-space: pre-line;">
                                        <?php echo esc_html($log->ai_response); ?>
                                    </div>
                                    <button type="button" class="button view-response" data-id="<?php echo esc_attr($log->id); ?>">
                                        <?php _e('View Full Response', 'chatgpt-fluent-connector'); ?>
                                    </button>
                                </td>
                                <td>
                                    <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log->created_at))); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php _e('No logs found.', 'chatgpt-fluent-connector'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Insert Test Record -->
            <?php if (current_user_can('manage_options')): ?>
                <div class="card" style="max-width: 600px; margin-top: 20px; padding: 10px 20px;">
                    <h3><?php _e('Insert Test Record', 'chatgpt-fluent-connector'); ?></h3>
                    <p><?php _e('You can insert a test record to verify that the logging system is working properly.', 'chatgpt-fluent-connector'); ?></p>
                    
                    <form method="post" action="">
                        <?php wp_nonce_field('cgptfc_insert_test_log', 'cgptfc_test_log_nonce'); ?>
                        <input type="hidden" name="cgptfc_insert_test_log" value="1">
                        <p>
                            <input type="submit" class="button button-primary" value="<?php _e('Insert Test Record', 'chatgpt-fluent-connector'); ?>">
                        </p>
                    </form>
                    
                    <?php
                    // Process test record insertion
                    if (isset($_POST['cgptfc_insert_test_log']) && check_admin_referer('cgptfc_insert_test_log', 'cgptfc_test_log_nonce')) {
                        $result = $this->log_response(
                            1, // prompt_id
                            1, // entry_id
                            1, // form_id
                            'Test prompt for diagnostics',
                            'This is a test response for diagnostic purposes. Generated at: ' . current_time('mysql')
                        );
                        
                        if ($result) {
                            echo '<div class="notice notice-success is-dismissible"><p>' . __('Test record inserted successfully. ID: ', 'chatgpt-fluent-connector') . $result . '</p></div>';
                        } else {
                            echo '<div class="notice notice-error is-dismissible"><p>' . __('Failed to insert test record. Please check the debug information.', 'chatgpt-fluent-connector') . '</p></div>';
                        }
                    }
                    ?>
                </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Response Modal -->
        <div id="response-modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div style="background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 800px;">
                <span style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;" id="close-modal">&times;</span>
                <h2><?php _e('ChatGPT Response', 'chatgpt-fluent-connector'); ?></h2>
                <div id="response-content" style="margin-top: 20px; white-space: pre-line;"></div>
            </div>
        </div>
        
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                // View Response Modal
                $('.view-response').click(function() {
                    var logId = $(this).data('id');
                    var responseText = $(this).prev('div').text();
                    
                    $('#response-content').text(responseText);
                    $('#response-modal').show();
                });
                
                // Close Modal
                $('#close-modal').click(function() {
                    $('#response-modal').hide();
                });
                
                // Close Modal when clicking outside
                $(window).click(function(event) {
                    if (event.target == $('#response-modal')[0]) {
                        $('#response-modal').hide();
                    }
                });
            });
        </script>
        <?php
    }
}