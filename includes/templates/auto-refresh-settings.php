<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

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