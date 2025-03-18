<?php
/**
 * Admin settings template
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Extract vars from parent scope
$api = $this->api;
$options = $options ?? [];
?>
<div class="wrap airtable-connector-admin">
    <h1><?php _e('Airtable Connector Settings', 'airtable-connector'); ?></h1>
    
    <div class="airtable-admin-header">
        <a href="?page=<?php echo AIRTABLE_CONNECTOR_SLUG; ?>&reset=1" class="button" 
           onclick="return confirm('Are you sure you want to reset all settings to defaults?');">
            <?php _e('Reset to Defaults', 'airtable-connector'); ?>
        </a>
    </div>
    
    <div class="airtable-settings-container">
        <!-- Settings Column -->
        <div class="airtable-column">
            <form method="post" action="" id="airtable-settings-form">
                <?php settings_fields(AIRTABLE_CONNECTOR_SLUG . '-settings-group'); ?>
                
                <!-- API Configuration -->
                <div class="airtable-card">
                    <h2><?php _e('API Configuration', 'airtable-connector'); ?></h2>
                    <table class="form-table airtable-form-table">
                        <tr>
                            <th scope="row">
                                <label for="api_title"><?php _e('API Name', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="api_title" name="api_title" 
                                       value="<?php echo esc_attr($options['api_title'] ?? 'Default API'); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('A friendly name for this API connection.', 'airtable-connector'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php if (!empty($options['api_id'])) : ?>
                        <tr>
                            <th scope="row">
                                <?php _e('API ID', 'airtable-connector'); ?>
                            </th>
                            <td>
                                <code><?php echo esc_html($options['api_id']); ?></code>
                                <p class="description">
                                    <?php _e('Unique identifier for this API connection. Use in shortcodes: [airtable-api-', 'airtable-connector'); ?><?php echo esc_html(substr($options['api_id'], 4)); ?>]
                                </p>
                            </td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th scope="row">
                                <label for="api_key"><?php _e('API Key', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="api_key" name="api_key" 
                                       value="<?php echo esc_attr($options['api_key'] ?? ''); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('Your Airtable API key/Bearer Token.', 'airtable-connector'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="base_id"><?php _e('Base ID', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="base_id" name="base_id" 
                                       value="<?php echo esc_attr($options['base_id'] ?? ''); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('The Airtable Base ID (e.g., "appURtLsEk5ZdoL7f").', 'airtable-connector'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="table_name"><?php _e('Table Name', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="table_name" name="table_name" 
                                       value="<?php echo esc_attr($options['table_name'] ?? ''); ?>" class="regular-text">
                                <p class="description">
                                    <?php _e('The table name (e.g., "Leads") or table ID.', 'airtable-connector'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label><?php _e('Filters', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <div id="airtable-filters-container">
                                    <?php 
                                    // Get filters from options
                                    $filters = isset($options['filters']) ? $options['filters'] : [];
                                    
                                    // Ensure we always have at least one filter row
                                    if (empty($filters)) {
                                        $filters[] = ['field' => '', 'value' => ''];
                                    }
                                    
                                    foreach ($filters as $index => $filter) : ?>
                                        <div class="filter-row">
                                            <input type="text" 
                                                   name="filters[<?php echo $index; ?>][field]" 
                                                   value="<?php echo esc_attr($filter['field'] ?? ''); ?>" 
                                                   placeholder="<?php _e('Field Name (e.g., Type)', 'airtable-connector'); ?>" 
                                                   class="filter-field">
                                            
                                            <input type="text" 
                                                   name="filters[<?php echo $index; ?>][value]" 
                                                   value="<?php echo esc_attr($filter['value'] ?? ''); ?>" 
                                                   placeholder="<?php _e('Field Value (e.g., Resort)', 'airtable-connector'); ?>" 
                                                   class="filter-value">
                                            
                                            <?php if ($index > 0) : ?>
                                                <button type="button" class="button remove-filter">
                                                    <?php _e('Remove', 'airtable-connector'); ?>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <button type="button" id="add-filter" class="button">
                                    <?php _e('Add Another Filter', 'airtable-connector'); ?>
                                </button>
                                
                                <p class="description filter-description">
                                    <?php _e('All filters are combined with AND logic. Field names and values are case-sensitive.', 'airtable-connector'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" id="test-connection" class="button button-secondary">
                                    <?php _e('Test Connection', 'airtable-connector'); ?>
                                </button>
                                <span id="connection-status"></span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Display Fields -->
                <div class="airtable-card" id="fields-container">
                    <h2><?php _e('Display Fields', 'airtable-connector'); ?></h2>
                    <p><?php _e('Select fields to display in the output.', 'airtable-connector'); ?></p>
                    
                    <div id="fields-selector">
                        <?php 
                        // Get all available fields from the last API response
                        $available_fields = [];
                        $api_response = isset($options['last_api_response']) ? $options['last_api_response'] : [];
                        
                        if (!empty($api_response) && !empty($api_response['data']['records'])) {
                            foreach ($api_response['data']['records'] as $record) {
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
                        
                        if (empty($available_fields)) : ?>
                        <p class="no-fields-message">
                            <?php _e('Available fields will appear here after testing the connection.', 'airtable-connector'); ?>
                        </p>
                        <?php else : ?>
                            <?php foreach ($available_fields as $field) : 
                                $is_checked = in_array($field, (array)($options['fields_to_display'] ?? [])); ?>
                                <label class="field-checkbox">
                                    <input type="checkbox" name="fields_to_display[]" 
                                          value="<?php echo esc_attr($field); ?>" <?php checked($is_checked); ?>>
                                    <?php echo esc_html($field); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Cache Settings -->
                <div class="airtable-card">
                    <h2><?php _e('Cache Settings', 'airtable-connector'); ?></h2>
                    <table class="form-table airtable-form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_cache"><?php _e('Enable Cache', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="enable_cache" name="enable_cache" value="1" 
                                       <?php checked(!empty($options['enable_cache'])); ?>>
                                <span class="description">
                                    <?php _e('Cache API responses to improve performance.', 'airtable-connector'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cache_time"><?php _e('Cache Duration', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="cache_time" name="cache_time" 
                                       value="<?php echo esc_attr($options['cache_time'] ?? '5'); ?>" min="1" max="1440" class="small-text">
                                <span class="description">
                                    <?php _e('Time in minutes before cache expires.', 'airtable-connector'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="show_cache_info"><?php _e('Show Cache Info', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="show_cache_info" name="show_cache_info" value="1" 
                                       <?php checked(!empty($options['show_cache_info'])); ?>>
                                <span class="description">
                                    <?php _e('Show last updated timestamp in output.', 'airtable-connector'); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Auto-Refresh Settings -->
                <div class="airtable-card">
                    <h2><?php _e('Auto-Refresh Settings', 'airtable-connector'); ?></h2>
                    <table class="form-table airtable-form-table">
                        <tr>
                            <th scope="row">
                                <label for="enable_auto_refresh"><?php _e('Enable Auto-Refresh', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="checkbox" id="enable_auto_refresh" name="enable_auto_refresh" value="1" 
                                       <?php checked(!empty($options['enable_auto_refresh'])); ?>>
                                <span class="description">
                                    <?php _e('Automatically reload the page to refresh data.', 'airtable-connector'); ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="auto_refresh_interval"><?php _e('Refresh Interval', 'airtable-connector'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="auto_refresh_interval" name="auto_refresh_interval" 
                                       value="<?php echo esc_attr($options['auto_refresh_interval'] ?? '60'); ?>" min="5" max="3600" class="small-text">
                                <span class="description">
                                    <?php _e('Time in seconds between page refreshes.', 'airtable-connector'); ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Shortcode Usage -->
                <div class="airtable-card">
                    <h2><?php _e('Shortcode Usage', 'airtable-connector'); ?></h2>
                    <p>
                        <?php _e('Use this shortcode to display Airtable data:', 'airtable-connector'); ?>
                        <code>[airtable_simple title="My Data" columns="3"]</code>
                    </p>
                    <p class="description">
                        <?php _e('Parameters:', 'airtable-connector'); ?><br>
                        <code>title</code> - <?php _e('Title to display above the data', 'airtable-connector'); ?><br>
                        <code>columns</code> - <?php _e('Number of columns to display (default: 3)', 'airtable-connector'); ?><br>
                        <code>filter_field</code> - <?php _e('Override the filter field setting', 'airtable-connector'); ?><br>
                        <code>filter_value</code> - <?php _e('Override the filter value setting', 'airtable-connector'); ?><br>
                        <code>refresh</code> - <?php _e('Set to "yes" to bypass cache (default: "no")', 'airtable-connector'); ?>
                    </p>
                </div>
                
                <!-- Additional Shortcodes for this API -->
                <div class="airtable-card">
                    <h2><?php _e('Multiple API Connections', 'airtable-connector'); ?></h2>
                    <p>
                        <?php _e('Your API connection is uniquely identified by:', 'airtable-connector'); ?>
                    </p>
                    <ul>
                        <li><?php _e('Name-based shortcode:', 'airtable-connector'); ?> <code>[<?php echo esc_html(sanitize_title($options['api_title'] ?? 'default-api')); ?>]</code></li>
                        <li><?php _e('ID-based shortcode:', 'airtable-connector'); ?> <code>[airtable-api-<?php echo esc_html(substr($options['api_id'] ?? 'undefined', 4)); ?>]</code></li>
                        <li><?php _e('Refresh button shortcode:', 'airtable-connector'); ?> <code>[show_refresh_button-<?php echo esc_html(substr($options['api_id'] ?? 'undefined', 4)); ?>]</code></li>
                    </ul>
                    <p>
                        <em><?php _e('Support for multiple API connections will be available in a future update.', 'airtable-connector'); ?></em>
                    </p>
                </div>
                
                <p class="submit">
                    <button type="submit" name="save_settings" class="button-primary">
                        <?php _e('Save Settings', 'airtable-connector'); ?>
                    </button>
                </p>
            </form>
        </div>
        
        <!-- Data Column -->
        <div class="airtable-column">
            <div class="airtable-card" id="api-response-container">
                <h2><?php _e('API Response', 'airtable-connector'); ?></h2>
                
                <div id="api-response-content">
                    <?php 
                    // Display saved API response if available
                    if (!empty($options['last_api_response']) && !empty($options['last_api_response']['data']['records'])) {
                        $response = $options['last_api_response'];
                        $record_count = count($response['data']['records'] ?? []);
                        
                        echo '<div class="airtable-success">';
                        echo '<p><strong>' . __('Success!', 'airtable-connector') . '</strong> ' . __('Connection to Airtable is working properly.', 'airtable-connector') . '</p>';
                        echo '<p><strong>' . __('Records Retrieved:', 'airtable-connector') . '</strong> ' . $record_count . '</p>';
                        
                        // Show filter information if applied
                        if (!empty($response['filter_applied'])) {
                            if (!empty($response['filters']) && count($response['filters']) > 0) {
                                if (count($response['filters']) === 1) {
                                    echo '<p><strong>' . __('Filter Applied:', 'airtable-connector') . '</strong> ' . 
                                        esc_html($response['filters'][0]['field']) . ' = "' . 
                                        esc_html($response['filters'][0]['value']) . '"</p>';
                                } else {
                                    echo '<p><strong>' . __('Filters Applied:', 'airtable-connector') . '</strong></p>';
                                    echo '<ul class="filter-list">';
                                    
                                    foreach ($response['filters'] as $filter) {
                                        echo '<li>' . esc_html($filter['field']) . ' = "' . esc_html($filter['value']) . '"</li>';
                                    }
                                    
                                    echo '</ul>';
                                }
                                echo '<p><strong>' . __('Filtered Records Found:', 'airtable-connector') . '</strong> ' . 
                                    esc_html($response['filtered_record_count']) . '</p>';
                            }
                        }
                        
                        if (!empty($response['url'])) {
                            echo '<p><strong>' . __('API URL:', 'airtable-connector') . '</strong> ' . esc_html($response['url']) . '</p>';
                        }
                        
                        echo '</div>';
                        
                        // Add the JSON data
                        echo '<h3>' . __('API Response', 'airtable-connector') . '</h3>';
                        echo '<div class="api-response-json">';
                        echo '<pre>' . json_encode($response['data'], JSON_PRETTY_PRINT) . '</pre>';
                        echo '</div>';
                    } else {
                        echo '<p class="no-data-message">';
                        echo __('Test the connection to see API response data.', 'airtable-connector');
                        echo '</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear Cache Button Outside the Main Settings Form -->
    <form method="post" action="" class="clear-cache-form">
        <div class="airtable-card">
            <h2><?php _e('Cache Management', 'airtable-connector'); ?></h2>
            <p>
                <button type="submit" name="clear_cache" class="button">
                    <?php _e('Clear All Cache', 'airtable-connector'); ?>
                </button>
                <span class="description">
                    <?php _e('Remove all cached Airtable data.', 'airtable-connector'); ?>
                </span>
            </p>
        </div>
    </form>
</div>