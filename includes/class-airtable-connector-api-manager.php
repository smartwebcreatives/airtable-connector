<?php
/**
 * Class file for Airtable Connector API Manager
 *
 * @package Airtable_Connector
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Manages multiple API connections
 */
class Airtable_Connector_API_Manager {
    
    /**
     * Get all registered APIs
     */
    public static function get_all_apis() {
        $options = get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, []);
        
        // For future multi-API structure
        if (isset($options['apis']) && is_array($options['apis'])) {
            return $options['apis'];
        }
        
        // Current single API structure - convert to array format
        if (isset($options['api_id'])) {
            return [
                $options['api_id'] => [
                    'id' => $options['api_id'],
                    'title' => $options['api_title'] ?? 'Default API',
                    'type' => $options['api_type'] ?? 'airtable',
                    'settings' => $options
                ]
            ];
        }
        
        return [];
    }
    
    /**
     * Get a specific API by ID
     */
    public static function get_api($api_id) {
        $apis = self::get_all_apis();
        
        if (isset($apis[$api_id])) {
            return $apis[$api_id];
        }
        
        return null;
    }
    
    /**
     * Get supported API types
     */
    public static function get_api_types() {
        return [
            'airtable' => [
                'name' => 'Airtable',
                'icon' => 'dashicons-database',
                'description' => 'Connect to Airtable bases and tables.'
            ],
            // Future API types will be added here
            'googlesheets' => [
                'name' => 'Google Sheets',
                'icon' => 'dashicons-spreadsheet',
                'description' => 'Connect to Google Sheets (coming soon).',
                'coming_soon' => true
            ]
        ];
    }
}