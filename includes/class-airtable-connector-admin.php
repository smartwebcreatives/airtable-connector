<?php
/**
 * Class file for Airtable Connector Admin
 *
 * @package Airtable_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles admin settings page
 */
class Airtable_Connector_Admin {
    
    /**
     * API instance
     */
    private $api;
    
    /**
     * Cache instance
     */
    private $cache;
    
    /**
     * Constructor
     */
    public function __construct($api, $cache = null) {
        $this->api = $api;
        $this->cache = $cache;
        
        // Add admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Register settings
        add_action('admin_init', [$this, 'register_settings']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // Add AJAX handler for testing API connection
        add_action('wp_ajax_airtable_connector_test_connection', [$this, 'test_connection_ajax']);
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, AIRTABLE_CONNECTOR_SLUG) !== false) {
            // Add CSS
            wp_enqueue_style(
                'airtable-connector-admin', 
                AIRTABLE_CONNECTOR_PLUGIN_URL . 'assets/css/admin.css',
                [],
                AIRTABLE_CONNECTOR_VERSION
            );
            
            // Add JS
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'airtable-connector-admin',
                AIRTABLE_CONNECTOR_PLUGIN_URL . 'assets/js/admin.js',
                ['jquery'],
                AIRTABLE_CONNECTOR_VERSION,
                true
            );
            
            // Add inline script with ajax url
            wp_localize_script('airtable-connector-admin', 'airtableConnector', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('airtable_connector_nonce')
            ]);
        }
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            AIRTABLE_CONNECTOR_SLUG . '-settings-group',
            AIRTABLE_CONNECTOR_OPTIONS_KEY
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            'Airtable Connector',
            'Airtable',
            'manage_options',
            AIRTABLE_CONNECTOR_SLUG,
            [$this, 'settings_page'],
            'dashicons-database',
            30
        );
    }
    
    /**
     * AJAX handler for testing API connection
     */
    public function test_connection_ajax() {
        // Check nonce
        check_ajax_referer('airtable_connector_nonce', 'nonce');
        
        // Get parameters
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        $base_id = isset($_POST['base_id']) ? sanitize_text_field($_POST['base_id']) : '';
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        
        // Process filters
        $filters = [];
        
        // Handle multiple filters from UI
        if (isset($_POST['filters']) && is_array($_POST['filters'])) {
            foreach ($_POST['filters'] as $filter) {
                if (isset($filter['field'], $filter['value']) && !empty($filter['field']) && $filter['value'] !== '') {
                    $filters[] = [
                        'field' => sanitize_text_field($filter['field']),
                        'value' => sanitize_text_field($filter['value'])
                    ];
                }
            }
        }
        
        // Test connection
        $options = [
            'api_key' => $api_key,
            'base_id' => $base_id,
            'table_name' => $table_name,
            'filters' => $filters
        ];
        
        // Get current options to merge with test options
        $current_options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // Create temporary options for testing that preserves other settings
        $test_options = array_merge($current_options, $options);
        
        $result = $this->api->get_airtable_data($test_options);
        
        // Save the result in the options if successful
        if ($result['success']) {
            // Update the last_api_response in the options
            $current_options['last_api_response'] = $result;
            update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $current_options);
        }
        
        // Return the complete result including data
        wp_send_json($result);
        wp_die();
    }
    
/**
 * Clear the options in the database
 * This is a helper method to reset to defaults
 */
private function reset_options() {
    // Define default options
    $default_options = [
        'api_title' => 'Default API',
        'api_id' => 'api_' . uniqid(), // Generate a new ID when resetting
        'api_key' => '',
        'base_id' => '',
        'table_name' => '',
        'fields_to_display' => [],
        'filters' => [],
        'last_api_response' => [],
        'enable_cache' => '1',
        'cache_time' => '5',
        'show_cache_info' => '1',
        'enable_auto_refresh' => '',
        'auto_refresh_interval' => '60'
    ];
    
    // Update with empty defaults rather than deleting completely
    update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
}
    
    /**
     * Settings page
     */
    public function settings_page() {
        // Process clear cache action
        if (isset($_POST['clear_cache']) && $this->cache) {
            $count = $this->cache->clear_cache();
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 sprintf(_n('%s cache entry cleared.', '%s cache entries cleared.', $count, 'airtable-connector'), 
                 number_format_i18n($count)) . '</p></div>';
        } 
        
        // Check if we need to reset options
        if (isset($_GET['reset']) && $_GET['reset'] === '1') {
            $this->reset_options();
            echo '<div class="notice notice-success is-dismissible"><p>All settings have been reset to defaults.</p></div>';
        }
        
        // Define default options
        $default_options = [
            'api_key' => '',
            'base_id' => '',
            'table_name' => '',
            'fields_to_display' => [],
            'filters' => [],
            'last_api_response' => [],
            'enable_cache' => '1',
            'cache_time' => '5',
            'show_cache_info' => '1',
            'enable_auto_refresh' => '',
            'auto_refresh_interval' => '60'
        ];
        
        // Get saved options with defaults as fallback
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
        
        // Ensure all expected keys exist in the options array
        $options = wp_parse_args($options, $default_options);
        
        // Check for POST submission - more explicit check for the submit button
if (isset($_POST['submit']) || isset($_POST['save_settings'])) {
    // Add these two lines at the beginning of this section:
    // API settings
    $options['api_title'] = isset($_POST['api_title']) ? sanitize_text_field($_POST['api_title']) : 'Default API';
    
    // Generate API ID if it doesn't exist
    if (empty($options['api_id'])) {
        $options['api_id'] = 'api_' . uniqid();
    }
    
    // Original code continues here:
    $options['api_key'] = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
    $options['base_id'] = isset($_POST['base_id']) ? sanitize_text_field($_POST['base_id']) : '';
    $options['table_name'] = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
    
            // Process filters
            $filters = [];
            if (isset($_POST['filters']) && is_array($_POST['filters'])) {
                foreach ($_POST['filters'] as $filter) {
                    $field = isset($filter['field']) ? sanitize_text_field($filter['field']) : '';
                    $value = isset($filter['value']) ? sanitize_text_field($filter['value']) : '';
                    
                    if (!empty($field) && $value !== '') {
                        $filters[] = [
                            'field' => $field,
                            'value' => $value
                        ];
                    }
                }
            }
            $options['filters'] = $filters;
            
            // Fields to display
            if (isset($_POST['fields_to_display']) && is_array($_POST['fields_to_display'])) {
                $fields_to_display = [];
                foreach ($_POST['fields_to_display'] as $field) {
                    $fields_to_display[] = sanitize_text_field($field);
                }
                $options['fields_to_display'] = $fields_to_display;
            } else {
                $options['fields_to_display'] = [];
            }
            
            // Cache settings - explicit handling
            $options['enable_cache'] = isset($_POST['enable_cache']) ? '1' : '';
            $options['cache_time'] = isset($_POST['cache_time']) ? intval($_POST['cache_time']) : 5;
            // Enforce minimum/maximum values
            $options['cache_time'] = max(1, min(1440, $options['cache_time']));
            $options['show_cache_info'] = isset($_POST['show_cache_info']) ? '1' : '';
            
            // Auto-refresh settings - explicit handling
            $options['enable_auto_refresh'] = isset($_POST['enable_auto_refresh']) ? '1' : '';
            $options['auto_refresh_interval'] = isset($_POST['auto_refresh_interval']) ? intval($_POST['auto_refresh_interval']) : 60;
            // Enforce minimum/maximum values
            $options['auto_refresh_interval'] = max(5, min(3600, $options['auto_refresh_interval']));
            
            // Clear cache if settings were changed
            if ($this->cache) {
                $this->cache->clear_cache();
            }
            
            // Save the options
            update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $options);
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully.</p></div>';
        }
        
        // Include the admin template
        include_once AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/templates/admin-settings.php';
    }
}