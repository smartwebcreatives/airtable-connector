# Airtable Connector WordPress Plugin

A lightweight WordPress plugin that allows you to display Airtable data on your website with simple shortcodes.

## Features

- **Simple Integration**: Connect to your Airtable bases with minimal configuration
- **Numeric Shortcodes**: Easy-to-remember shortcodes using simple numeric IDs (e.g., `[airtable-001]`)
- **Customizable Display**: Choose which fields to display and how they appear
- **Responsive Grid Layout**: Data automatically adjusts to different screen sizes
- **Powerful Filtering**: Show only the records that match your criteria
- **Performance Optimization**: Built-in caching system reduces API calls
- **Auto-Refresh**: Keep your displayed data current with configurable refresh intervals
- **User Controls**: Optional refresh buttons and countdown timers

## Installation

1. Upload the `airtable-connector` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to the 'Airtable' menu in your admin dashboard to configure settings

## Configuration

### API Settings

- **API Name**: A friendly name for this API connection
- **Numeric ID**: The unique ID used in shortcodes (automatically generated)
- **API Key**: Your Airtable API key/Bearer Token 
- **Base ID**: The Airtable Base ID (e.g., "appURtLsEk5ZdoL7f")
- **Table Name**: The table name (e.g., "Leads") or table ID
- **Filters**: Multiple field/value pairs for filtering data

### Display Options

- **Fields to Display**: Select which fields from your Airtable data to show
- **Layout Options**: Configure the number of columns for different screen sizes

### Cache Settings

- **Enable Cache**: Toggle caching of API responses
- **Cache Duration**: Set how long data should be cached (1-1440 minutes)
- **Show Cache Info**: Option to display last updated timestamp in output

### Auto-Refresh Settings

- **Enable Auto-Refresh**: Automatically reload the page to refresh data
- **Refresh Interval**: Time in seconds between refreshes (5-3600 seconds)

## Usage

### Basic Shortcode

```
[airtable-001]
```

Where "001" is your unique numeric ID shown in the admin settings.

### Customized Shortcode

```
[airtable-001 title="Team Members" columns="4" filter_field="Department" filter_value="Marketing" refresh="no" show_refresh_button="yes" show_countdown="yes"]
```

### Advanced Grid Layout

```
[airtable-001 grid="d3,t2,ml2,m1"]
```
This sets 3 columns on desktop, 2 on tablet, 2 on mobile landscape, and 1 on mobile portrait.

### Refresh Button

```
[refresh-001 label="Update Data" class="button button-primary"]
```

## Shortcode Parameters

| Parameter | Description | Default |
|-----------|-------------|---------|
| `title` | Title to display above the data | "Airtable Data" |
| `columns` | Number of columns in grid | 3 |
| `grid` | Responsive grid settings (e.g., "d3,t2,ml2,m1") | Based on `columns` |
| `filter_field` | Field name to filter by | - |
| `filter_value` | Value to filter for | - |
| `refresh` | Set to "yes" to bypass cache | "no" |
| `show_refresh_button` | Set to "yes" to show a refresh button | "no" |
| `show_countdown` | Set to "yes" to show refresh countdown timer | "no" |
| `show_last_updated` | Set to "yes"/"no" to override global setting | - |
| `auto_refresh` | Set to "yes"/"no" to override global setting | - |
| `auto_refresh_interval` | Custom refresh interval in seconds | - |
| `id` | Custom ID for the shortcode instance | Auto-generated |

## Styling

The plugin includes basic responsive styles. You can customize the appearance by overriding the following CSS classes:

- `.airtable-connector-container`: Main container
- `.airtable-title`: Title heading
- `.airtable-filter-info`: Filter information display
- `.airtable-controls`: Container for buttons and info
- `.airtable-grid`: Grid container
- `.airtable-item`: Individual record container
- `.airtable-field`: Field container
- `.airtable-field-label`: Field name
- `.airtable-field-value`: Field value

## Troubleshooting

### Common Issues

1. **API Connection Failures**
   - Verify your API key is correct
   - Confirm Base ID and Table Name are valid
   - Check if Airtable API rate limits have been reached

2. **Missing or Incomplete Data**
   - Ensure fields have been selected for display
   - Verify filter values match exactly (case-sensitive)
   - Check if the table structure has changed in Airtable

3. **Cache Issues**
   - Clear cache after making changes in Airtable
   - Use `refresh="yes"` to bypass cache temporarily
   - Reset plugin settings if cache becomes corrupted

4. **Display Problems**
   - Adjust the number of columns for better layout
   - Check for special characters in field names
   - Inspect browser console for JavaScript errors

## Upcoming Features

- Multiple API connections with independent settings
- Support for additional data sources beyond Airtable
- Advanced display templates and formatting options
- Direct frontend editing capabilities
- Custom field type handling (attachments, linked records, etc.)

## Developer Notes

The plugin uses a simple numeric ID system for shortcodes (e.g., `[airtable-001]`). This makes it easy for users to reference their connections and simplifies future multi-API support.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Jerry

## Changelog

### 1.0.0
- Initial release with basic Airtable integration

### 1.1.0
- Added numeric ID system for simplified shortcodes
- Improved responsive grid layout with custom column settings
- Enhanced admin interface with clearer shortcode documentation