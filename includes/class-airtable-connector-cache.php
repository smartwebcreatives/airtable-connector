<?php
/**
 * Class file for Airtable Connector Cache
 *
 * @package Airtable_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
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
     * Clear specific cache based on options
     * This allows targeted cache clearing for the "Update Now" button
     */
    public function clear_specific_cache($options) {
        $cache_key = $this->get_cache_key($options);
        return delete_transient($cache_key);
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