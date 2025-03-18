<?php
/**
 * All plugin classes in one file
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles all Airtable API interactions
 */
class Airtable_Connector_API {
    
    /**
     * Get Airtable data
     */
    public function get_airtable_data($options) {
        // Get saved options if not provided (for backward compatibility)
        if (empty($options)) {
            $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        }
        
        // Validate required fields
        if (empty($options['api_key']) || empty($options['base_id']) || empty($options['table_name'])) {
            return [
                'success' => false,
                'message' => 'Missing required API configuration (API Key, Base ID, or Table Name)',
                'url' => '',
                'data' => null
            ];
        }
        
        // Build URL - simple format like Bricksforge
        $base_id = trim($options['base_id']);
        $table_name = trim($options['table_name']);
        
        $url = "https://api.airtable.com/v0/{$base_id}/{$table_name}";
        
        // Process filters
        $filter_formula = '';
        $filters = [];
        
        // Multi-filter support
        if (!empty($options['filters']) && is_array($options['filters'])) {
            $filters = $options['filters'];
        } 
        // Legacy single filter support
        else if (!empty($options['filter_field']) && isset($options['filter_value']) && $options['filter_value'] !== '') {
            $filters[] = [
                'field' => $options['filter_field'],
                'value' => $options['filter_value']
            ];
        }
        
        // Build filter formula if we have filters
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $filter) {
                if (!empty($filter['field']) && isset($filter['value']) && $filter['value'] !== '') {
                    $conditions[] = "{" . $filter['field'] . "}=\"" . $filter['value'] . "\"";
                }
            }
            
            if (count($conditions) === 1) {
                $filter_formula = $conditions[0];
            } else if (count($conditions) > 1) {
                $filter_formula = "AND(" . implode(',', $conditions) . ")";
            }
            
            // Add filter to URL if we have a formula
            if (!empty($filter_formula)) {
                $encoded_filter = urlencode($filter_formula);
                $url .= "?filterByFormula=" . $encoded_filter;
            }
        }
        
        // Make the API request
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $options['api_key'],
                'Content-Type' => 'application/json'
            ],
            'timeout' => 30 // Increased timeout for larger datasets
        ]);
        
        // Process response
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message(),
                'url' => $url,
                'data' => null
            ];
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($response_code !== 200) {
            $error_message = "API returned status code: {$response_code}";
            if (isset($data['error']) && isset($data['error']['message'])) {
                $error_message .= " - " . $data['error']['message'];
            }
            
            return [
                'success' => false,
                'message' => $error_message,
                'url' => $url,
                'data' => $data
            ];
        }
        
        $result = [
            'success' => true,
            'message' => 'Data retrieved successfully',
            'url' => $url,
            'data' => $data,
            'record_count' => count($data['records'] ?? []),
            'timestamp' => time()  // Add timestamp for cache display
        ];
        
        // If we used filters, add filtered information
        if (!empty($filter_formula)) {
            $result['filter_applied'] = true;
            $result['filter_formula'] = $filter_formula;
            $result['filters'] = $filters;
            $result['filtered_record_count'] = count($data['records'] ?? []);
        }
        
        return $result;
    }
    
    /**
     * Get all available fields from the records
     */
    public function get_available_fields($airtable_data) {
        $available_fields = [];
        
        if (($airtable_data['success'] ?? false) && !empty($airtable_data['data']['records'])) {
            foreach ($airtable_data['data']['records'] as $record) {
                if (isset($record['fields']) && is_array($record['fields'])) {
                    foreach (array_keys($record['fields']) as $field) {
                        if (!in_array($field, $available_fields)) {
                            $available_fields[] = $field;
                        }
                    }
                }
            }
            sort($available_fields);
        }
        
        return $available_fields;
    }
}

/**
 * Handles shortcode functionality
 */
class Airtable_Connector_Shortcode {
    
    /**
     * API instance
     */
    private $api;
    
    /**
 * Constructor
 */
public function __construct($api, $cache = null) {
    $this->api = $api;
    $this->cache = $cache;
    
    // Add shortcode
    add_shortcode('airtable_simple', [$this, 'shortcode_handler']);
}
    
    /**
     * Shortcode handler
     */
    public function shortcode_handler($atts) {
        // Get options with defaults as fallback
        $default_options = [
            'api_key' => '',
            'base_id' => '',
            'table_name' => '',
            'fields_to_display' => [],
            'filters' => [],
            'last_api_response' => []
        ];
        
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
        
        // Ensure we have all expected keys
        $options = wp_parse_args($options, $default_options);
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'title' => 'Airtable Data',
                'columns' => 3,
                'filter_field' => '',
                'filter_value' => ''
            ),
            $atts,
            'airtable_simple'
        );
        
        // Override filter settings if provided in shortcode
        if (!empty($atts['filter_field']) && isset($atts['filter_value'])) {
            // Replace all filters with the one from the shortcode
            $options['filters'] = [
                [
                    'field' => $atts['filter_field'],
                    'value' => $atts['filter_value']
                ]
            ];
        }
        
        // Get data directly from API
        $result = $this->api->get_airtable_data($options);
        
        if (!($result['success'] ?? false)) {
            return '<div style="color: red; padding: 10px; border: 1px solid #ddd;">' . 
                   'Error fetching data: ' . esc_html($result['message'] ?? 'Unknown error') . 
                   '</div>';
        }
        
        $records = $result['data']['records'] ?? [];
        
        if (empty($records)) {
            return '<div style="padding: 10px; border: 1px solid #ddd;">No records found.</div>';
        }
        
        // Start output buffer
        ob_start();
        
        // Add title if provided
        if (!empty($atts['title'])) {
            echo '<h2>' . esc_html($atts['title']) . '</h2>';
        }
        
        // Add filter info if applied
        if (!empty($result['filter_applied'])) {
            echo '<div style="margin-bottom: 15px; font-style: italic;">';
            
            if (!empty($result['filters']) && count($result['filters']) > 0) {
                if (count($result['filters']) === 1) {
                    echo 'Filtered by ' . esc_html($result['filters'][0]['field']) . ': ' . esc_html($result['filters'][0]['value']);
                } else {
                    echo 'Filtered by multiple conditions: ';
                    echo '<ul style="margin-top: 5px; margin-bottom: 5px; margin-left: 20px;">';
                    
                    foreach ($result['filters'] as $filter) {
                        echo '<li>' . esc_html($filter['field']) . ': ' . esc_html($filter['value']) . '</li>';
                    }
                    
                    echo '</ul>';
                }
            } elseif (!empty($result['filter_formula'])) {
                echo 'Filtered by formula: ' . esc_html($result['filter_formula']);
            }
            
            echo ' (' . esc_html($result['filtered_record_count']) . ' records)';
            echo '</div>';
        }
        
        // Create grid
        echo '<div style="display: grid; grid-template-columns: repeat(' . intval($atts['columns']) . ', 1fr); gap: 20px;">';
        
        foreach ($records as $record) {
            echo '<div style="border: 1px solid #ddd; padding: 15px; background-color: #f9f9f9;">';
            
            // Display only selected fields
            foreach ((array)$options['fields_to_display'] as $field) {
                if (isset($record['fields'][$field])) {
                    echo '<p><strong>' . esc_html($field) . ':</strong> ' . esc_html($record['fields'][$field]) . '</p>';
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Return output
        return ob_get_clean();
    }
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
            'api_key' => '',
            'base_id' => '',
            'table_name' => '',
            'fields_to_display' => [],
            'filters' => [],
            'last_api_response' => [] // Store the last API response
        ];
        
        // Update with empty defaults rather than deleting completely
        update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
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
    'enable_cache' => '1',           // Enable cache by default
    'cache_time' => '5',             // 5 minutes cache time
    'show_cache_info' => '1',        // Show cache info by default
    'enable_auto_refresh' => '',     // Auto-refresh disabled by default
    'auto_refresh_interval' => '60'  // 60 seconds refresh interval
];
        
        // Get saved options with defaults as fallback
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
        
        // Ensure all expected keys exist in the options array
        $options = wp_parse_args($options, $default_options);
        
        // Save options if form is submitted
        if (isset($_POST['save_settings'])) {
            $options['api_key'] = sanitize_text_field($_POST['api_key']);
            $options['base_id'] = sanitize_text_field($_POST['base_id']);
            $options['table_name'] = sanitize_text_field($_POST['table_name']);
            
            // Process multiple filters
            $filters = [];
            if (isset($_POST['filters']) && is_array($_POST['filters'])) {
                foreach ($_POST['filters'] as $filter) {
                    $field = isset($filter['field']) ? sanitize_text_field($filter['field']) : '';
                    $value = isset($filter['value']) ? sanitize_text_field($filter['value']) : '';
                    
                    // Only add if both field and value are provided
                    if (!empty($field) && $value !== '') {
                        $filters[] = [
                            'field' => $field,
                            'value' => $value
                        ];
                    }
                }
            }
            $options['filters'] = $filters;
            
            // Handle fields to display - important for sanitization
            if (isset($_POST['fields_to_display']) && is_array($_POST['fields_to_display'])) {
                $fields_to_display = [];
                foreach ($_POST['fields_to_display'] as $field) {
                    $fields_to_display[] = sanitize_text_field($field);
                }
                $options['fields_to_display'] = $fields_to_display;
            } else {
                $options['fields_to_display'] = [];
            }
            
            // Save the options to the database
            update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $options);
            
            echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
        }
        
        // Include the admin template
        include_once AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/templates/admin-settings.php';
    }
}

/**
 * Handles caching functionality
 */
class Airtable_Connector_Cache {
    
    /**
     * Get cache key based on options
     */
    private function get_cache_key($options) {
        // Create a unique key based on options that affect data
        $key_data = [
            'base_id' => $options['base_id'] ?? '',
            'table_name' => $options['table_name'] ?? '',
            'filters' => $options['filters'] ?? []
        ];
        
        return 'airtable_data_' . md5(serialize($key_data));
    }
    
    /**
     * Get cached data if available and not expired
     */
    public function get_cached_data($options) {
        if (empty($options['enable_cache']) || empty($options['cache_time'])) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($options);
        $cached_data = get_transient($cache_key);
        
        if ($cached_data === false) {
            return false;
        }
        
        return $cached_data;
    }
    
    /**
     * Cache the data using WordPress transients
     */
    public function cache_data($options, $data) {
        if (empty($options['enable_cache']) || empty($options['cache_time'])) {
            return false;
        }
        
        $cache_key = $this->get_cache_key($options);
        $expiration = intval($options['cache_time']) * 60; // Convert minutes to seconds
        
        return set_transient($cache_key, $data, $expiration);
    }
    
    /**
     * Clear all Airtable cache
     */
    public function clear_cache() {
        global $wpdb;
        
        // Get all transients that start with our prefix
        $transients = $wpdb->get_col(
            "SELECT option_name FROM $wpdb->options 
            WHERE option_name LIKE '_transient_airtable_data_%'"
        );
        
        $count = 0;
        foreach ($transients as $transient) {
            $name = str_replace('_transient_', '', $transient);
            if (delete_transient($name)) {
                $count++;
            }
        }
        
        return $count;
    }
}