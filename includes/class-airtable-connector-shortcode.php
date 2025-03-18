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
                'filter_field' => '',
                'filter_value' => '',
                'refresh' => 'no' // Option to bypass cache
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
        
        // Check if we should use cache
        $use_cache = ($atts['refresh'] !== 'yes');
        
        // Get data with or without cache
        $result = null;
        
        if ($use_cache && !empty($options['enable_cache']) && $this->cache) {
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
        
        // Start output buffer
        ob_start();
        
        // Add timestamp if cached and show_cache_info is enabled
        if (!empty($result['timestamp']) && !empty($options['show_cache_info'])) {
            echo '<div style="font-size: 0.8em; color: #666; margin-bottom: 10px;">Last updated: ' . 
                 date('Y-m-d H:i:s', $result['timestamp']) . '</div>';
        }
        
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
        
        // Add auto-refresh if enabled
        if (!empty($options['enable_auto_refresh']) && !empty($options['auto_refresh_interval'])) {
            $interval = intval($options['auto_refresh_interval']) * 1000; // Convert to milliseconds
            
            echo '<script>
                setTimeout(function() {
                    location.reload();
                }, ' . $interval . ');
            </script>';
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