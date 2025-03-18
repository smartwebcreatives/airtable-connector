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
    
    <div class="airtable-settings-container">
        <!-- Left Column (2/3 width) -->
        <div class="airtable-column" style="flex: 2;">
            <form method="post" action="" id="airtable-settings-form">
                <?php settings_fields(AIRTABLE_CONNECTOR_SLUG . '-settings-group'); ?>
                
                <!-- API Configuration -->
                <div class="airtable-card">
                    <h2><?php _e('API Configuration', 'airtable-connector'); ?></h2>
                    <table class="form-table airtable-form-table">
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
                
                <!-- Row with Cache Settings and Auto-Refresh Settings side-by-side -->
                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <!-- Cache Settings -->
                    <div class="airtable-card" style="flex: 1;">
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
                    <div class="airtable-card" style="flex: 1;">
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
                </div>
                
                <!-- Submit button with Reset to Defaults button next to it -->
                <p class="submit" style="display: flex; gap: 10px;">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Save Settings', 'airtable-connector'); ?>">
                    <a href="?page=<?php echo AIRTABLE_CONNECTOR_SLUG; ?>&reset=1" class="button" 
                       onclick="return confirm('Are you sure you want to reset all settings to defaults?');">
                        <?php _e('Reset to Defaults', 'airtable-connector'); ?>
                    </a>
                </p>
            </form>
            
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
            
            <!-- Shortcode Usage moved below Cache Management as requested -->
            <div class="airtable-card">
                <h2><?php _e('Shortcode Usage', 'airtable-connector'); ?></h2>
                <p>
                    <?php _e('Basic usage:', 'airtable-connector'); ?>
                    <code>[airtable_simple]</code>
                </p>
                
                <p>
                    <?php _e('Full example with all parameters:', 'airtable-connector'); ?>
                    <code>[airtable_simple title="My Data" grid="d3,t2,ml2,m1" filter_field="Status" filter_value="Active" show_refresh_button="yes" show_countdown="yes" show_last_updated="yes" auto_refresh="yes" auto_refresh_interval="30" id="my-table"]</code>
                </p>
                
                <p class="description">
                    <?php _e('Available parameters:', 'airtable-connector'); ?>
                </p>
                
                <table class="wp-list-table widefat fixed striped" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php _e('Parameter', 'airtable-connector'); ?></th>
                            <th><?php _e('Description', 'airtable-connector'); ?></th>
                            <th><?php _e('Default', 'airtable-connector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td><?php _e('Title to display above the data', 'airtable-connector'); ?></td>
                            <td>"Airtable Data"</td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td><?php _e('Number of columns to display (simple version of grid parameter)', 'airtable-connector'); ?></td>
                            <td>3</td>
                        </tr>
                        <tr>
                            <td><code>grid</code></td>
                            <td>
                                <?php _e('Responsive grid layout with format "d3,t2,ml2,m1":', 'airtable-connector'); ?><br>
                                <ul style="margin-top: 5px; margin-left: 15px;">
                                    <li>d3 = desktop (3 columns)</li>
                                    <li>t2 = tablet (2 columns)</li>
                                    <li>ml2 = mobile landscape (2 columns)</li>
                                    <li>m1 = mobile portrait (1 column)</li>
                                </ul>
                            </td>
                            <td>"d3,t2,ml2,m1"</td>
                        </tr>
                        <tr>
                            <td><code>filter_field</code></td>
                            <td><?php _e('Override the filter field setting', 'airtable-connector'); ?></td>
                            <td>empty</td>
                        </tr>
                        <tr>
                            <td><code>filter_value</code></td>
                            <td><?php _e('Override the filter value setting', 'airtable-connector'); ?></td>
                            <td>empty</td>
                        </tr>
                        <tr>
                            <td><code>refresh</code></td>
                            <td><?php _e('Set to "yes" to bypass cache (one-time refresh)', 'airtable-connector'); ?></td>
                            <td>"no"</td>
                        </tr>
                        <tr>
                            <td><code>show_refresh_button</code></td>
                            <td><?php _e('Set to "yes" to show a manual refresh button', 'airtable-connector'); ?></td>
                            <td>"no"</td>
                        </tr>
                        <tr>
                            <td><code>show_countdown</code></td>
                            <td><?php _e('Set to "yes" to show countdown timer until next refresh', 'airtable-connector'); ?></td>
                            <td>"no"</td>
                        </tr>
                        <tr>
                            <td><code>show_last_updated</code></td>
                            <td><?php _e('Set to "yes" or "no" to override global setting for displaying last updated timestamp', 'airtable-connector'); ?></td>
                            <td>uses global setting</td>
                        </tr>
                        <tr>
                            <td><code>auto_refresh</code></td>
                            <td><?php _e('Set to "yes" or "no" to override global setting for auto-refresh', 'airtable-connector'); ?></td>
                            <td>uses global setting</td>
                        </tr>
                        <tr>
                            <td><code>auto_refresh_interval</code></td>
                            <td><?php _e('Time in seconds between auto-refreshes (5-3600)', 'airtable-connector'); ?></td>
                            <td>uses global setting</td>
                        </tr>
                        <tr>
                            <td><code>id</code></td>
                            <td><?php _e('Custom ID for the shortcode instance (useful for multiple shortcodes on same page)', 'airtable-connector'); ?></td>
                            <td>auto-generated</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Right Column (1/3 width) -->
        <div class="airtable-column" style="flex: 1;">
            <!-- API Response -->
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
</div>