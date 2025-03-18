# Airtable Connector Plugin Documentation

## Overview

The Airtable Connector plugin enables WordPress users to fetch and display data from Airtable bases directly on their websites. It provides a simple shortcode interface for displaying Airtable data in a customizable grid layout, with support for filtering, caching, and auto-refresh capabilities.

## Plugin Architecture

The plugin follows a modular architecture with the following components:

### Main Components

1. **API Handler** (`class-airtable-connector-api.php`)
   * Manages all interactions with the Airtable API
   * Handles authentication, data retrieval, and filtering

2. **Cache Manager** (`class-airtable-connector-cache.php`)
   * Provides caching functionality to improve performance
   * Uses WordPress transients for temporary storage
   * Includes methods for clearing cache

3. **Shortcode Handler** (`class-airtable-connector-shortcode.php`)
   * Processes the `[airtable_simple]` shortcode
   * Renders Airtable data in a responsive grid format
   * Supports filtering and display options

4. **Admin Interface** (`class-airtable-connector-admin.php`)
   * Creates and manages the plugin settings page
   * Provides API connection testing
   * Handles field selection and display options

5. **Loader** (`class-airtable-connector-loader.php`)
   * Manages plugin initialization
   * Handles dependencies between components
   * Provides singleton access to plugin instances

### Directory Structure

```
airtable-connector/
├── airtable-connector.php (main plugin file)
├── includes/
│   ├── class-airtable-connector-api.php
│   ├── class-airtable-connector-shortcode.php
│   ├── class-airtable-connector-admin.php
│   ├── class-airtable-connector-cache.php
│   ├── class-airtable-connector-loader.php
│   └── templates/
│       └── admin-settings.php
├── assets/
│   ├── css/
│   │   └── admin.css
│   └── js/
│       └── admin.js
```

## Detailed Component Documentation

### 1. API Handler (Airtable_Connector_API)

The API handler manages all interactions with the Airtable API, including authentication, data retrieval, and filtering.

#### Key Methods:

- **get_airtable_data($options)**
  * Purpose: Fetches data from Airtable using the provided options
  * Parameters:
    * `$options` (array): Array of options including API key, base ID, table name, and filters
  * Returns: Array containing:
    * `success` (boolean): Whether the request was successful
    * `message` (string): Success/error message
    * `url` (string): The API URL used for the request
    * `data` (array): The retrieved data from Airtable
    * `record_count` (int): Number of records retrieved
    * `timestamp` (int): Unix timestamp of when data was retrieved
    * Additional filter information if filters were applied

- **get_available_fields($airtable_data)**
  * Purpose: Extracts all unique field names from Airtable records
  * Parameters:
    * `$airtable_data` (array): Data returned from `get_airtable_data()`
  * Returns: Array of field names sorted alphabetically

### 2. Cache Manager (Airtable_Connector_Cache)

The cache manager provides caching functionality to improve performance by storing API responses temporarily.

#### Key Methods:

- **get_cached_data($options)**
  * Purpose: Retrieves cached data if available and not expired
  * Parameters:
    * `$options` (array): Options containing cache settings and identifiers
  * Returns: Cached data array or `false` if not available

- **cache_data($options, $data)**
  * Purpose: Stores data in the cache
  * Parameters:
    * `$options` (array): Options containing cache settings
    * `$data` (array): Data to cache
  * Returns: Boolean indicating success/failure

- **clear_cache()**
  * Purpose: Clears all plugin cache entries
  * Returns: Number of cache entries cleared

- **get_cache_key($options)** (private)
  * Purpose: Generates a unique cache key based on options
  * Parameters:
    * `$options` (array): Options used for identification
  * Returns: String cache key

### 3. Shortcode Handler (Airtable_Connector_Shortcode)

The shortcode handler processes the `[airtable_simple]` shortcode and renders Airtable data.

#### Key Methods:

- **__construct($api, $cache)**
  * Purpose: Initializes the shortcode handler and registers the shortcode
  * Parameters:
    * `$api` (Airtable_Connector_API): API handler instance
    * `$cache` (Airtable_Connector_Cache): Cache manager instance

- **shortcode_handler($atts)**
  * Purpose: Processes the shortcode attributes and renders the output
  * Parameters:
    * `$atts` (array): Shortcode attributes
  * Returns: HTML string containing the rendered output

#### Shortcode Usage:

```
[airtable_simple title="My Data" columns="3" filter_field="Status" filter_value="Active" refresh="no"]
```

#### Shortcode Parameters:

- `title` (string): Title to display above the data
- `columns` (int): Number of columns in the grid (default: 3)
- `filter_field` (string): Field name to filter by
- `filter_value` (string): Value to filter for
- `refresh` (string): Set to "yes" to bypass cache (default: "no")

### 4. Admin Interface (Airtable_Connector_Admin)

The admin interface creates and manages the plugin settings page.

#### Key Methods:

- **__construct($api, $cache)**
  * Purpose: Initializes the admin interface and registers hooks
  * Parameters:
    * `$api` (Airtable_Connector_API): API handler instance
    * `$cache` (Airtable_Connector_Cache): Cache manager instance

- **enqueue_admin_scripts($hook)**
  * Purpose: Loads CSS and JavaScript for the admin interface
  * Parameters:
    * `$hook` (string): Current admin page hook

- **add_admin_menu()**
  * Purpose: Adds the plugin menu item to the WordPress admin menu

- **test_connection_ajax()**
  * Purpose: AJAX handler for testing the Airtable API connection

- **settings_page()**
  * Purpose: Renders the plugin settings page
  * Manages settings form submission
  * Handles cache clearing and settings reset

- **reset_options()** (private)
  * Purpose: Resets plugin options to defaults

### 5. Loader (Airtable_Connector_Loader)

The loader manages plugin initialization and dependency injection.

#### Key Methods:

- **get_instance()**
  * Purpose: Gets the singleton instance of the loader
  * Returns: Loader instance

- **load_dependencies()** (private)
  * Purpose: Loads all required plugin files

- **initialize_components()** (private)
  * Purpose: Creates instances of all plugin components
  * Manages dependencies between components

## Plugin Settings

### API Configuration

- **API Key**: Airtable API key/Bearer Token
- **Base ID**: Airtable Base ID (e.g., "appURtLsEk5ZdoL7f")
- **Table Name**: The table name (e.g., "Leads") or table ID
- **Filters**: Multiple field/value pairs for filtering data

### Display Fields

- Selection of fields to display from the available fields in Airtable

### Cache Settings

- **Enable Cache**: Toggle caching of API responses
- **Cache Duration**: Time in minutes before cache expires (1-1440)
- **Show Cache Info**: Display last updated timestamp in output

### Auto-Refresh Settings

- **Enable Auto-Refresh**: Automatically reload the page to refresh data
- **Refresh Interval**: Time in seconds between refreshes (5-3600)

## Common Operations

### Testing Connection

The admin interface includes a "Test Connection" button to verify Airtable API connectivity. This:
1. Sends a request to Airtable using the provided credentials
2. Displays success/failure status
3. Shows retrieved records count and filter information
4. Updates the available fields selector

### Clearing Cache

Cache can be cleared via:
1. The "Clear All Cache" button in the admin interface
2. Automatically when settings are changed
3. By using the shortcode with `refresh="yes"`

### Resetting Settings

All plugin settings can be reset to defaults using the "Reset to Defaults" button in the admin interface.

## Common Use Cases

### Basic Data Display

To display Airtable data in its simplest form:

```
[airtable_simple]
```

### Filtered Data Display

To display only records matching specific criteria:

```
[airtable_simple filter_field="Category" filter_value="Project"]
```

### Custom Layout

To customize the grid layout and title:

```
[airtable_simple title="Team Members" columns="4"]
```

### Real-Time Data

To always fetch the latest data from Airtable:

```
[airtable_simple refresh="yes"]
```

## Development Notes

### Important Constants

- `AIRTABLE_CONNECTOR_VERSION`: Plugin version
- `AIRTABLE_CONNECTOR_PLUGIN_DIR`: Plugin directory path
- `AIRTABLE_CONNECTOR_PLUGIN_URL`: Plugin directory URL
- `AIRTABLE_CONNECTOR_SLUG`: Plugin slug for admin pages
- `AIRTABLE_CONNECTOR_OPTIONS_KEY`: Options key in the database

### Filter Formula Construction

The plugin constructs Airtable filter formulas in the following format:

- Single filter: `{FieldName}="Value"`
- Multiple filters: `AND({Field1}="Value1",{Field2}="Value2",...)`

### Error Handling

The plugin includes error handling for:
- Missing required API configuration
- API connection failures
- API response errors
- Invalid or missing data

### Performance Considerations

- API requests are cached to reduce load on Airtable and improve performance
- Cache duration is configurable
- Multiple filters are combined into a single API request using AND logic

## Extending the Plugin

### Adding New Display Formats

To add new display formats beyond the default grid:

1. Create a new method in the Shortcode class for the alternative format
2. Add a new shortcode parameter to select the display format
3. Modify the `shortcode_handler()` method to call the appropriate format method

### Adding Advanced Filtering

To implement more advanced filtering capabilities:

1. Enhance the filter formula construction in the API class
2. Add support for OR logic and other Airtable formula operators
3. Implement a more sophisticated shortcode attribute parser

### Supporting Custom Field Types

To better handle Airtable's special field types:

1. Add field type detection in the API response processing
2. Implement custom rendering for attachments, linked records, etc.
3. Add options for controlling how different field types are displayed

## Troubleshooting

### Common Issues and Solutions

1. **API Connection Failures**
   - Verify API key is correct
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

### Debug Information

When troubleshooting API issues, the admin interface displays:
- The complete API URL used
- The HTTP response code
- The full JSON response from Airtable
- Filter information if filters were applied

## Future Enhancements

Potential improvements for future versions:

1. **Enhanced Display Options**
   - Additional layout templates (cards, list, table)
   - Support for Airtable attachments (images, files)
   - Custom CSS classes for styling

2. **Advanced Filtering**
   - Support for OR logic and other operators
   - Date range filters
   - Numeric comparison filters

3. **User Interaction**
   - Frontend filtering and sorting
   - Pagination for large datasets
   - Search functionality

4. **Integration Improvements**
   - Support for multiple Airtable bases
   - Form submission to Airtable
   - Webhook support for real-time updates