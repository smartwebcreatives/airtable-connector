<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>

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
        <tr>
            <th scope="row">
                <label><?php _e('Clear Cache', 'airtable-connector'); ?></label>
            </th>
            <td>
                <form method="post" style="display: inline;">
                    <button type="submit" name="clear_cache" class="button">
                        <?php _e('Clear All Cache', 'airtable-connector'); ?>
                    </button>
                </form>
                <span class="description">
                    <?php _e('Remove all cached Airtable data.', 'airtable-connector'); ?>
                </span>
            </td>
        </tr>
    </table>
</div>