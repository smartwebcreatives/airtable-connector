<?php  
/**  
 * Plugin Name: Airtable Connector  
 * Description: Displays Airtable data with customizable fields and filters
 * Version: 1.0.0  
 * Author: Testing Again  
 * Text Domain: airtable-connector  
 * License: GPL-2.0+  
 */

// Exit if accessed directly  
if (!defined('ABSPATH')) {  
    exit;  
}

// Define plugin constants  
define('AIRTABLE_CONNECTOR_VERSION', '1.0.0');  
define('AIRTABLE_CONNECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));  
define('AIRTABLE_CONNECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));  
define('AIRTABLE_CONNECTOR_SLUG', 'airtable-connector');  
define('AIRTABLE_CONNECTOR_OPTIONS_KEY', 'airtable_connector_options');

// Ensure directories exist
function airtable_connector_ensure_directories() {
    $directories = [
        AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes',
        AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/templates',
        AIRTABLE_CONNECTOR_PLUGIN_DIR . 'assets',
        AIRTABLE_CONNECTOR_PLUGIN_DIR . 'assets/css',
        AIRTABLE_CONNECTOR_PLUGIN_DIR . 'assets/js',
    ];
    
    foreach ($directories as $directory) {
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}

// Activation hook
register_activation_hook(__FILE__, 'airtable_connector_activate');

function airtable_connector_activate() {
    // Make sure directories exist
    airtable_connector_ensure_directories();
    
    // Initialize default settings if not exists
    if (!get_option(AIRTABLE_CONNECTOR_OPTIONS_KEY)) {
        $default_options = [
            'api_key' => '',
            'base_id' => '',
            'table_name' => '',
            'fields_to_display' => [],
            'filters' => [],
            'last_api_response' => []
        ];
        
        add_option(AIRTABLE_CONNECTOR_OPTIONS_KEY, $default_options);
    }
}

// Include all classes if they exist
function airtable_connector_load_files() {
    $classes_file = AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/classes.php';
    
    if (file_exists($classes_file)) {
        require_once $classes_file;
        return true;
    }
    
    return false;
}

// Initialize plugin  
function airtable_connector_init() {
    // Ensure files and directories exist
    airtable_connector_ensure_directories();
    
    // Try to load classes
    if (airtable_connector_load_files()) {
        // Create instances  
        $api = new Airtable_Connector_API();  
        $shortcode = new Airtable_Connector_Shortcode($api);  
          
        // Load admin only when needed  
        if (is_admin()) {  
            $admin = new Airtable_Connector_Admin($api);  
        }
    } else {
        // Add an admin notice if files are missing
        add_action('admin_notices', 'airtable_connector_missing_files_notice');
    }
}

// Admin notice for missing files
function airtable_connector_missing_files_notice() {
    ?>
    <div class="notice notice-error">
        <p><strong>Airtable Connector:</strong> Required files are missing. Please make sure all plugin files are properly installed.</p>
    </div>
    <?php
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'airtable_connector_deactivate');

function airtable_connector_deactivate() {
    // No action needed on deactivation
}