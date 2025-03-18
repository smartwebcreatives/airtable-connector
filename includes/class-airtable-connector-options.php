<?php
/**
 * Class file for Airtable Connector Options
 *
 * @package Airtable_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles options management with support for multiple APIs
 */
class Airtable_Connector_Options {
    
    /**
     * Get all API configurations
     */
    public static function get_all_apis() {
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // If we have the legacy format (single API), convert it
        if (isset($options['api_key']) && !isset($options['apis'])) {
            // This is old format, convert to new format
            $api_id = isset($options['api_id']) ? $options['api_id'] : 'api_' . uniqid();
            $api_title = isset($options['api_title']) ? $options['api_title'] : 'Default API';
            
            // Create the new structure
            $apis = [
                $api_id => [
                    'api_title' => $api_title,
                    'api_id' => $api_id,
                    'api_key' => $options['api_key'] ?? '',
                    'base_id' => $options['base_id'] ?? '',
                    'table_name' => $options['table_name'] ?? '',
                    'fields_to_display' => $options['fields_to_display'] ?? [],
                    'filters' => $options['filters'] ?? [],
                    'last_api_response' => $options['last_api_response'] ?? [],
                    'enable_cache' => $options['enable_cache'] ?? '1',
                    'cache_time' => $options['cache_time'] ?? '5',
                    'show_cache_info' => $options['show_cache_info'] ?? '1',
                    'enable_auto_refresh' => $options['enable_auto_refresh'] ?? '',
                    'auto_refresh_interval' => $options['auto_refresh_interval'] ?? '60',
                ]
            ];
            
            // Update with new structure but keep other settings
            $new_options = array_merge($options, ['apis' => $apis]);
            
            // Remove old keys
            unset($new_options['api_key'], $new_options['base_id'], $new_options['table_name'], 
                  $new_options['fields_to_display'], $new_options['filters'], 
                  $new_options['last_api_response']);
            
            // Save the new format
            update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $new_options);
            
            return $apis;
        }
        
        // Return the apis array or empty array if not set
        return $options['apis'] ?? [];
    }
    
    /**
     * Get a specific API configuration
     */
    public static function get_api($api_id) {
        $apis = self::get_all_apis();
        
        // Return the specific API if exists
        if (isset($apis[$api_id])) {
            return $apis[$api_id];
        }
        
        // If this is the first API and none exist yet
        if (empty($apis)) {
            $default_api = [
                'api_title' => 'Default API',
                'api_id' => 'api_' . uniqid(),
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
                'auto_refresh_interval' => '60',
            ];
            
            // Save this as the first API
            self::save_api($default_api['api_id'], $default_api);
            
            return $default_api;
        }
        
        // If API ID wasn't found but APIs exist, return the first one
        return reset($apis);
    }
    
    /**
     * Save an API configuration
     */
    public static function save_api($api_id, $api_data) {
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // Ensure apis key exists
        if (!isset($options['apis'])) {
            $options['apis'] = [];
        }
        
        // Update the specific API
        $options['apis'][$api_id] = $api_data;
        
        // Save changes
        update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $options);
        
        return true;
    }
    
    /**
     * Delete an API configuration
     */
    public static function delete_api($api_id) {
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // If apis key doesn't exist, nothing to delete
        if (!isset($options['apis'])) {
            return false;
        }
        
        // Remove the specific API
        if (isset($options['apis'][$api_id])) {
            unset($options['apis'][$api_id]);
            update_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $options);
            return true;
        }
        
        return false;
    }
}