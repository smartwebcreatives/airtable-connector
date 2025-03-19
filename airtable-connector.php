<?php  
/**  
 * Plugin Name: Airtable Connector  
 * Description: Displays Airtable data with customizable fields and filters
 * Version: 1.0.0  
 * Author: Learning 
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
            'api_title' => 'Default API',
            'api_id' => 'api_' . uniqid(),
            'numeric_id' => '001', // Add the numeric ID
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
// This function is now obsolete and can be replaced if needed
function airtable_connector_load_files() {
    // The loader class now handles loading all required files
    return true;
}

// Initialize plugin  
function airtable_connector_init() {
    // Ensure files and directories exist
    airtable_connector_ensure_directories();
    
    // Load the main loader class
    require_once AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/class-airtable-connector-loader.php';
    
    // Initialize the plugin components through the loader
    $loader = Airtable_Connector_Loader::get_instance();
    
    // If loader failed, show admin notice
    if (null === $loader) {
        add_action('admin_notices', 'airtable_connector_missing_files_notice');

        // Create empty frontend JS file if it doesn't exist
$frontend_js_file = AIRTABLE_CONNECTOR_PLUGIN_DIR . 'assets/js/frontend.js';
if (!file_exists($frontend_js_file)) {
    file_put_contents($frontend_js_file, '/**
 * Frontend JavaScript for Airtable Connector
 */');
}

// Create empty frontend CSS file if it doesn't exist
$frontend_css_file = AIRTABLE_CONNECTOR_PLUGIN_DIR . 'assets/css/frontend.css';
if (!file_exists($frontend_css_file)) {
    file_put_contents($frontend_css_file, '/**
 * Frontend CSS for Airtable Connector
 */');
}
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

// At the end of your airtable-connector.php file, add this line:
add_action('plugins_loaded', 'airtable_connector_init');

// Add inline CSS for card styling
function airtable_connector_add_inline_styles() {
    echo '<style>
    /* Card styling with maximum specificity */
    html body div.airtable-connector-container div.airtable-item {
        border: 2px solid #e0e0e0 !important;
        border-radius: 8px !important;
        padding: 20px !important;
        background-color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
        margin-bottom: 15px !important;
    }

    /* Force the hover effect */
    html body div.airtable-connector-container div.airtable-item:hover {
        transform: translateY(-3px) !important;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15) !important;
        transition: all 0.3s ease !important;
    }

    /* Apply field styling */
    html body div.airtable-connector-container div.airtable-field {
        padding-bottom: 10px !important;
        border-bottom: 1px solid #f0f0f0 !important;
        margin-bottom: 10px !important;
    }

    /* Ensure grid spacing */
    html body div.airtable-connector-container div.airtable-grid {
        gap: 24px !important;
    }
    </style>';
}
add_action('wp_head', 'airtable_connector_add_inline_styles');