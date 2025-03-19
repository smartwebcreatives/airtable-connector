<?php
/**
 * Shortcode display component for admin dashboard
 * 
 * This template part displays copyable shortcodes in the admin UI
 * 
 * @package Airtable_Connector
 * 
 * To use, include the following in your admin-settings.php template:
 * require_once AIRTABLE_CONNECTOR_PLUGIN_DIR . 'includes/templates/shortcode-display-component.php';
 * airtable_connector_render_shortcode_display($options['numeric_id'] ?? '001');
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Renders the shortcode display component
 * 
 * @param string $numeric_id The numeric ID of the current API connection
 */
function airtable_connector_render_shortcode_display($numeric_id) {
    ?>
    <div class="airtable-card" id="shortcode-display">
        <h2>Airtable-<?php echo esc_html($numeric_id); ?> Shortcodes</h2>
        
        <div class="shortcode-display-container">
            <div class="shortcode-row">
                <span class="shortcode-name">Display Airtable Data:</span>
                <div class="inline-code-with-copy">
                    <code id="display-shortcode">[airtable-<?php echo esc_html($numeric_id); ?>]</code>
                </div>
            </div>
            
            <div class="shortcode-row">
                <span class="shortcode-name">Refresh Button:</span>
                <div class="inline-code-with-copy">
                    <code id="refresh-shortcode">[refresh-<?php echo esc_html($numeric_id); ?>]</code>
                </div>
            </div>
        </div>
        
        <p class="shortcode-hint">Click on a shortcode to copy it to your clipboard</p>
    </div>
    <?php
}
?>