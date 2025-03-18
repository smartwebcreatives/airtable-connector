<?php
/**
 * Class file for Airtable Connector API
 *
 * @package Airtable_Connector
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