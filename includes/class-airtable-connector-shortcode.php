<?php
/**
 * Class file for Airtable Connector Shortcode
 *
 * @package Airtable_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
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
     * Cache instance
     */
    private $cache;
    
    /**
 * Constructor
 */
public function __construct($api, $cache = null) {
    $this->api = $api;
    $this->cache = $cache;
    
    // Get options
    $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
    
    // Register the standard shortcode
    add_shortcode('airtable_simple', [$this, 'shortcode_handler']);
    
    // Register shortcode using the API title (slug version)
    if (!empty($options['api_title'])) {
        $api_slug = sanitize_title($options['api_title']);
        add_shortcode($api_slug, [$this, 'shortcode_handler']);
    }
    
    // Register shortcode using the API ID number
    if (!empty($options['api_id'])) {
        // Extract the numeric part of the ID
        $api_id_numeric = substr($options['api_id'], 4); // Remove 'api_' prefix
        add_shortcode('airtable-api-' . $api_id_numeric, [$this, 'shortcode_handler']);
    }
    
    // Register refresh button shortcode
    add_shortcode('show_refresh_button', [$this, 'refresh_button_shortcode']);
    
    // Register ID-specific refresh button shortcode
    if (!empty($options['api_id'])) {
        $api_id_numeric = substr($options['api_id'], 4);
        add_shortcode('show_refresh_button-' . $api_id_numeric, [$this, 'refresh_button_shortcode']);
    }
}
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Only enqueue if shortcode is used
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'airtable_simple')) {
            wp_enqueue_script(
                'airtable-connector-frontend',
                AIRTABLE_CONNECTOR_PLUGIN_URL . 'assets/js/frontend.js',
                ['jquery'],
                AIRTABLE_CONNECTOR_VERSION,
                true
            );
            
            wp_localize_script('airtable-connector-frontend', 'airtableConnector', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('airtable_connector_frontend_nonce')
            ]);
            
            // Add frontend CSS
            wp_enqueue_style(
                'airtable-connector-frontend',
                AIRTABLE_CONNECTOR_PLUGIN_URL . 'assets/css/frontend.css',
                [],
                AIRTABLE_CONNECTOR_VERSION
            );
        }
    }
    
    /**
     * AJAX handler for the update now button
     */
    public function update_now_ajax() {
        // Check nonce
        check_ajax_referer('airtable_connector_frontend_nonce', 'nonce');
        
        // Get parameters
        $shortcode_id = isset($_POST['shortcode_id']) ? sanitize_text_field($_POST['shortcode_id']) : '';
        $base_id = isset($_POST['base_id']) ? sanitize_text_field($_POST['base_id']) : '';
        $table_name = isset($_POST['table_name']) ? sanitize_text_field($_POST['table_name']) : '';
        $filter_field = isset($_POST['filter_field']) ? sanitize_text_field($_POST['filter_field']) : '';
        $filter_value = isset($_POST['filter_value']) ? sanitize_text_field($_POST['filter_value']) : '';
        
        if (empty($shortcode_id)) {
            wp_send_json_error(['message' => 'Invalid shortcode ID']);
            wp_die();
        }
        
        // Get current options
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // Clear specific cache if possible
        if ($this->cache) {
            // Create temp options matching the shortcode for cache clearing
            $temp_options = $options;
            
            // Override with shortcode params if provided
            if (!empty($filter_field) && $filter_value !== '') {
                $temp_options['filters'] = [
                    [
                        'field' => $filter_field,
                        'value' => $filter_value
                    ]
                ];
            }
            
            if (!empty($base_id)) {
                $temp_options['base_id'] = $base_id;
            }
            
            if (!empty($table_name)) {
                $temp_options['table_name'] = $table_name;
            }
            
            // Clear specific cache
            $this->cache->clear_specific_cache($temp_options);
        }
        
        // Return success
        wp_send_json_success(['message' => 'Cache cleared and data refreshed']);
        wp_die();
    }
    
    /**
     * Parse grid parameter
     * Format: "d3,t2,ml2,m1" (desktop 3, tablet 2, mobile landscape 2, mobile 1)
     */
    private function parse_grid_param($grid_param) {
        $grid_settings = [
            'desktop' => 3,   // Default desktop columns
            'tablet' => 2,    // Default tablet columns
            'mobile_landscape' => 2, // Default mobile landscape columns
            'mobile' => 1     // Default mobile columns
        ];
        
        if (empty($grid_param)) {
            return $grid_settings;
        }
        
        // Parse the grid parameter
        $parts = explode(',', $grid_param);
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Desktop
            if (strpos($part, 'd') === 0) {
                $grid_settings['desktop'] = intval(substr($part, 1));
            }
            // Tablet
            else if (strpos($part, 't') === 0) {
                $grid_settings['tablet'] = intval(substr($part, 1));
            }
            // Mobile Landscape
            else if (strpos($part, 'ml') === 0) {
                $grid_settings['mobile_landscape'] = intval(substr($part, 2));
            }
            // Mobile
            else if (strpos($part, 'm') === 0) {
                $grid_settings['mobile'] = intval(substr($part, 1));
            }
            // Just a number (assume desktop)
            else if (is_numeric($part)) {
                $grid_settings['desktop'] = intval($part);
            }
        }
        
        return $grid_settings;
    }
    
    /**
     * Generate CSS for responsive grid
     */
    private function get_grid_css($shortcode_id, $grid_settings) {
        $css = "
            #{$shortcode_id} .airtable-grid {
                display: grid;
                gap: 20px;
                grid-template-columns: repeat({$grid_settings['desktop']}, 1fr);
            }
            
            @media (max-width: 992px) {
                #{$shortcode_id} .airtable-grid {
                    grid-template-columns: repeat({$grid_settings['tablet']}, 1fr);
                }
            }
            
            @media (max-width: 768px) {
                #{$shortcode_id} .airtable-grid {
                    grid-template-columns: repeat({$grid_settings['mobile_landscape']}, 1fr);
                }
            }
            
            @media (max-width: 576px) {
                #{$shortcode_id} .airtable-grid {
                    grid-template-columns: repeat({$grid_settings['mobile']}, 1fr);
                }
            }
        ";
        
        return $css;
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
            'last_api_response' => [],
            'enable_cache' => '1',
            'cache_time' => '5',
            'show_cache_info' => '1',
            'enable_auto_refresh' => '',
            'auto_refresh_interval' => '60'
        ];
        
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
        
        // Ensure we have all expected keys
        $options = wp_parse_args($options, $default_options);
        
        // Parse attributes
        $atts = shortcode_atts(
            array(
                'title' => 'Airtable Data',
                'columns' => 3,
                'grid' => '', // New responsive grid parameter
                'filter_field' => '',
                'filter_value' => '',
                'refresh' => 'no', // Option to bypass cache
                'show_refresh_button' => 'no', // Show manual refresh button
                'show_countdown' => 'no', // Show countdown timer
                'show_last_updated' => '', // Empty = use global setting, yes/no to override
                'auto_refresh' => '', // Empty = use global setting, yes/no to override
                'auto_refresh_interval' => '', // Empty = use global setting, number to override
                'id' => '' // Custom ID for the shortcode instance
            ),
            $atts,
            'airtable_simple'
        );
        
        // Generate a unique ID for this shortcode instance if not provided
        $shortcode_id = !empty($atts['id']) ? 
            'airtable-' . sanitize_title($atts['id']) : 
            'airtable-' . uniqid();
        
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
        
        // Determine auto-refresh settings
        $auto_refresh = !empty($options['enable_auto_refresh']);
        $refresh_interval = intval($options['auto_refresh_interval']);
        
        // Override with shortcode settings if provided
        if ($atts['auto_refresh'] === 'yes') {
            $auto_refresh = true;
        } else if ($atts['auto_refresh'] === 'no') {
            $auto_refresh = false;
        }
        
        if (!empty($atts['auto_refresh_interval'])) {
            $refresh_interval = intval($atts['auto_refresh_interval']);
            // Enforce min/max
            $refresh_interval = max(5, min(3600, $refresh_interval));
        }
        
        // Determine if we should show last updated timestamp
        $show_cache_info = !empty($options['show_cache_info']);
        if ($atts['show_last_updated'] === 'yes') {
            $show_cache_info = true;
        } else if ($atts['show_last_updated'] === 'no') {
            $show_cache_info = false;
        }
        
      // Check if page was refreshed with our parameter or button
$force_refresh = isset($_GET['refresh_airtable']) || ($atts['refresh'] === 'yes');

// Get data with or without cache
$result = null;

if (!$force_refresh && !empty($options['enable_cache']) && $this->cache) {
    // Try to get from cache first
    $result = $this->cache->get_cached_data($options);
}
        
        // If not in cache or cache disabled, get from API
        if ($result === false || $result === null) {
            $result = $this->api->get_airtable_data($options);
            
            // Store in cache for future use if caching is enabled
            if (!empty($options['enable_cache']) && $this->cache) {
                $this->cache->cache_data($options, $result);
            }
        }
        
        if (!($result['success'] ?? false)) {
            return '<div style="color: red; padding: 10px; border: 1px solid #ddd;">' . 
                   'Error fetching data: ' . esc_html($result['message'] ?? 'Unknown error') . 
                   '</div>';
        }
        
        $records = $result['data']['records'] ?? [];
        
        if (empty($records)) {
            return '<div style="padding: 10px; border: 1px solid #ddd;">No records found.</div>';
        }
        
        // Parse grid settings
        $grid_settings = $this->parse_grid_param($atts['grid'] ?: $atts['columns']);
        
        // Start output buffer
        ob_start();
        
        // Add container with ID for targeting
        echo '<div id="' . esc_attr($shortcode_id) . '" class="airtable-connector-container" data-shortcode-id="' . esc_attr($shortcode_id) . '">';
        
        // Add the header container
        echo '<div class="airtable-header">';
        
        // Add title if provided
        if (!empty($atts['title'])) {
            echo '<h2 class="airtable-title">' . esc_html($atts['title']) . '</h2>';
        }
        
        // Add filter info if applied
        if (!empty($result['filter_applied'])) {
            echo '<div class="airtable-filter-info">';
            
            if (!empty($result['filters']) && count($result['filters']) > 0) {
                if (count($result['filters']) === 1) {
                    echo '<span>Filtered by ' . esc_html($result['filters'][0]['field']) . ': ' . esc_html($result['filters'][0]['value']) . '</span>';
                } else {
                    echo '<span>Filtered by multiple conditions:</span>';
                    echo '<ul class="airtable-filter-list">';
                    
                    foreach ($result['filters'] as $filter) {
                        echo '<li>' . esc_html($filter['field']) . ': ' . esc_html($filter['value']) . '</li>';
                    }
                    
                    echo '</ul>';
                }
            } elseif (!empty($result['filter_formula'])) {
                echo '<span>Filtered by formula: ' . esc_html($result['filter_formula']) . '</span>';
            }
            
            echo ' <span class="airtable-record-count">(' . esc_html($result['filtered_record_count']) . ' records)</span>';
            echo '</div>';
        }
        
        echo '</div>'; // End .airtable-header
        
        // Add controls container
        $show_controls = $atts['show_refresh_button'] === 'yes' || $atts['show_countdown'] === 'yes' || $show_cache_info;
        if ($show_controls) {
            echo '<div class="airtable-controls">';
            
            // Show last updated timestamp if enabled
            if ($show_cache_info && !empty($result['timestamp'])) {
                echo '<div class="airtable-last-updated">Last updated: ' . 
                     '<span>' . date('Y-m-d H:i:s', $result['timestamp']) . '</span>' .
                     '</div>';
            }
            
            // Show refresh button if enabled
            if ($atts['show_refresh_button'] === 'yes') {
                echo '<button class="airtable-update-now" data-shortcode-id="' . esc_attr($shortcode_id) . '" ' .
                     'data-base-id="' . esc_attr($options['base_id']) . '" ' .
                     'data-table-name="' . esc_attr($options['table_name']) . '" ' .
                     'data-filter-field="' . esc_attr($atts['filter_field']) . '" ' .
                     'data-filter-value="' . esc_attr($atts['filter_value']) . '">' .
                     'Update Now</button>';
            }
            
            // Show countdown timer if enabled and auto-refresh is on
            if ($atts['show_countdown'] === 'yes' && $auto_refresh) {
                echo '<div class="airtable-countdown">Next update in: <span class="airtable-timer" ' .
                     'data-interval="' . esc_attr($refresh_interval) . '">00:00</span></div>';
            }
            
            echo '</div>'; // End .airtable-controls
        }
        
        // Inline CSS for the grid
        echo '<style type="text/css">' . $this->get_grid_css($shortcode_id, $grid_settings) . '</style>';
        
        // Create grid
        echo '<div class="airtable-grid">';
        
        foreach ($records as $record) {
            echo '<div class="airtable-item">';
            
            // Display only selected fields
            foreach ((array)$options['fields_to_display'] as $field) {
                if (isset($record['fields'][$field])) {
                    echo '<div class="airtable-field">' .
                         '<span class="airtable-field-label">' . esc_html($field) . ':</span> ' .
                         '<span class="airtable-field-value">' . esc_html($record['fields'][$field]) . '</span>' .
                         '</div>';
                }
            }
            
            echo '</div>';
        }
        
        echo '</div>'; // End .airtable-grid
        
        // Add auto-refresh if enabled
        if ($auto_refresh) {
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var shortcodeId = "' . esc_js($shortcode_id) . '";
                    var interval = ' . esc_js($refresh_interval) . ';
                    
                    // Set up refresh interval
                    setTimeout(function() {
                        location.reload();
                    }, interval * 1000);
                    
                    // Start countdown timer if enabled
                    if (document.querySelector("#' . esc_js($shortcode_id) . ' .airtable-timer")) {
                        startCountdown(shortcodeId, interval);
                    }
                });
            </script>';
        } else if ($atts['show_countdown'] === 'yes') {
            // No auto-refresh, but countdown requested - show disabled state
            echo '<script>
                document.addEventListener("DOMContentLoaded", function() {
                    var timerElement = document.querySelector("#' . esc_js($shortcode_id) . ' .airtable-timer");
                    if (timerElement) {
                        timerElement.textContent = "Auto-refresh disabled";
                    }
                });
            </script>';
        }
        
        echo '</div>'; // End container
        
        // Return output
        return ob_get_clean();
    }

    /**
 * Refresh button shortcode handler
 */
public function refresh_button_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(
        array(
            'label' => 'Refresh Data',
            'class' => 'button',
        ),
        $atts,
        'show_refresh_button'
    );
    
    // Create a simple button that refreshes the page with a no-cache parameter
    $current_url = remove_query_arg('refresh_airtable');
    $refresh_url = add_query_arg('refresh_airtable', '1', $current_url);
    
    // Output the button
    $output = '<a href="' . esc_url($refresh_url) . '" class="' . esc_attr($atts['class']) . '">';
    $output .= esc_html($atts['label']);
    $output .= '</a>';
    
    return $output;
}
}