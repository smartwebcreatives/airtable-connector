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

/**
 * Add inline CSS styles with maximum specificity for Airtable Connector
 * Add this function to your theme's functions.php or the main plugin file
 */
function airtable_connector_add_inline_styles() {
    echo '<style>
    /* Container styling */
    html body div.airtable-connector-container {
        margin: 2rem 0 !important;
        font-size: inherit !important;
        line-height: 1.5 !important;
        color: #333 !important;
    }

    /* Title styling */
    html body div.airtable-connector-container h2.airtable-title {
        margin-top: 0 !important;
        margin-bottom: 1rem !important;
        font-size: 1.5rem !important;
        font-weight: 600 !important;
        color: #333 !important;
    }

    /* Filter information styling */
    html body div.airtable-connector-container div.airtable-filter-info {
        margin-bottom: 1rem !important;
        font-style: italic !important;
        color: #666 !important;
        font-size: 0.9rem !important;
    }

    /* Controls section styling */
    html body div.airtable-connector-container div.airtable-controls {
        display: flex !important;
        flex-wrap: wrap !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 1.5rem !important;
        padding: 0.75rem 1rem !important;
        background-color: #f8f9fa !important;
        border: 1px solid #e9ecef !important;
        border-radius: 4px !important;
    }

    /* Update button styling */
    html body div.airtable-connector-container button.airtable-update-now {
        background-color: #f1f3f5 !important;
        color: #495057 !important;
        border: 1px solid #dee2e6 !important;
        padding: 0.5rem 1rem !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        font-size: 0.9rem !important;
        transition: all 0.2s ease !important;
    }

    html body div.airtable-connector-container button.airtable-update-now:hover {
        background-color: #e9ecef !important;
        border-color: #ced4da !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
    }

    /* Grid layout */
    html body div.airtable-connector-container div.airtable-grid {
        display: grid !important;
        gap: 1.5rem !important;
    }

    /* Card styling with maximum specificity */
    html body div.airtable-connector-container div.airtable-item {
        border: 1px solid #e9ecef !important;
        padding: 1.25rem !important;
        background-color: #fff !important;
        border-radius: 6px !important;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        transition: all 0.2s ease !important;
    }

    /* Force the hover effect */
    html body div.airtable-connector-container div.airtable-item:hover {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06) !important;
        transform: translateY(-2px) !important;
    }

    /* Apply field styling */
    html body div.airtable-connector-container div.airtable-field {
        margin-bottom: 0.75rem !important;
        padding-bottom: 0.75rem !important;
        border-bottom: 1px solid #f5f5f5 !important;
    }

    html body div.airtable-connector-container div.airtable-field:last-child {
        margin-bottom: 0 !important;
        padding-bottom: 0 !important;
        border-bottom: none !important;
    }

    /* Field label styling */
    html body div.airtable-connector-container span.airtable-field-label {
        font-weight: 600 !important;
        color: #495057 !important;
        margin-right: 0.25rem !important;
    }

    /* Refresh button shortcode styling */
    html body a.button {
        display: inline-block !important;
        background-color: #f1f3f5 !important;
        color: #495057 !important;
        text-decoration: none !important;
        padding: 0.5rem 1rem !important;
        border-radius: 4px !important;
        border: 1px solid #dee2e6 !important;
        font-size: 0.9rem !important;
        transition: all 0.2s ease !important;
    }

    html body a.button:hover {
        background-color: #e9ecef !important;
        border-color: #ced4da !important;
        text-decoration: none !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05) !important;
    }

    /* Responsive adjustments */
    @media screen and (max-width: 768px) {
        html body div.airtable-connector-container div.airtable-controls {
            flex-direction: column !important;
            align-items: flex-start !important;
        }
        
        html body div.airtable-connector-container div.airtable-controls > div,
        html body div.airtable-connector-container div.airtable-controls > button {
            margin-bottom: 0.75rem !important;
        }
    }
    </style>';
}
add_action('wp_head', 'airtable_connector_add_inline_styles');