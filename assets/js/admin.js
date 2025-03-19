/**
 * Admin JavaScript for Airtable Connector
 */

/// Shortcode clipboard functionality
function initializeShortcodeClipboard() {
    // Handle click on shortcode code elements
    document.querySelectorAll('#shortcode-display code').forEach(function(element) {
        element.addEventListener('click', function() {
            copyToClipboard(this.textContent);
            showCopiedMessage(this.parentNode);
        });
    });
    
    // Handle click on copy icons
    document.querySelectorAll('.copy-icon').forEach(function(icon) {
        icon.addEventListener('click', function() {
            const targetId = this.getAttribute('data-clipboard-target');
            const shortcodeElement = document.querySelector(targetId);
            if (shortcodeElement) {
                copyToClipboard(shortcodeElement.textContent);
                showCopiedMessage(this.parentNode);
            }
        });
    });
}

// Copy text to clipboard
function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        // Modern approach for secure contexts
        navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
}

// Show copied message
function showCopiedMessage(parentElement) {
    // Remove any existing copied messages
    document.querySelectorAll('.shortcode-copied').forEach(function(msg) {
        msg.remove();
    });
    
    // Create and add the copied message
    const message = document.createElement('span');
    message.className = 'shortcode-copied';
    message.textContent = 'Copied!';
    parentElement.appendChild(message);
    
    // Make it visible (animate)
    setTimeout(function() {
        message.classList.add('visible');
    }, 10);
    
    // Remove after a delay
    setTimeout(function() {
        message.classList.remove('visible');
        setTimeout(function() {
            message.remove();
        }, 300);
    }, 2000);
}

jQuery(document).ready(function($) {
// Initialize shortcode clipboard functionality
initializeShortcodeClipboard();

    // Handle adding new filter
    $('#add-filter').on('click', function(e) {
        e.preventDefault();
        
        // Get the current number of filters
        var filterCount = $('.filter-row').length;
        
        // Create new filter row
        var newRow = 
            '<div class="filter-row">' +
                '<input type="text" ' +
                       'name="filters[' + filterCount + '][field]" ' +
                       'value="" ' +
                       'placeholder="Field Name (e.g., Type)" ' +
                       'class="filter-field regular-text">' +
                
                '<input type="text" ' +
                       'name="filters[' + filterCount + '][value]" ' +
                       'value="" ' +
                       'placeholder="Field Value (e.g., Resort)" ' +
                       'class="filter-value regular-text">' +
                
                '<button type="button" class="button remove-filter">' +
                    'Remove' +
                '</button>' +
            '</div>';
        
        // Add the new row to the container
        $('#airtable-filters-container').append(newRow);
    });
    
    // Handle removing a filter
    $(document).on('click', '.remove-filter', function(e) {
        e.preventDefault();
        $(this).closest('.filter-row').remove();
        
        // Reindex the remaining filters
        $('.filter-row').each(function(index) {
            $(this).find('input.filter-field').attr('name', 'filters[' + index + '][field]');
            $(this).find('input.filter-value').attr('name', 'filters[' + index + '][value]');
        });
    });
    
    // Test connection button
    $('#test-connection').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $status = $('#connection-status');
        var $responseContainer = $('#api-response-content');
        
        // Get basic values
        var apiKey = $('#api_key').val();
        var baseId = $('#base_id').val();
        var tableName = $('#table_name').val();
        
        // Get filters
        var filters = [];
        $('.filter-row').each(function() {
            var field = $(this).find('.filter-field').val();
            var value = $(this).find('.filter-value').val();
            
            if (field && value) {
                filters.push({
                    field: field,
                    value: value
                });
            }
        });
        
        // Validate required inputs
        if (!apiKey || !baseId || !tableName) {
            $status.html('<span style="color: red;">Please fill in all required fields (API Key, Base ID, Table Name).</span>');
            return;
        }
        
        // Show loading state
        $button.prop('disabled', true);
        $status.html('<span style="color: #999;">Testing connection...</span>');
        $responseContainer.html('<p style="color: #666;"><span class="dashicons dashicons-update" style="animation: rotation 2s infinite linear;"></span> Loading data...</p>');
        
        // Make AJAX request
        $.ajax({
            url: airtableConnector.ajaxUrl,
            type: 'POST',
            data: {
                action: 'airtable_connector_test_connection',
                nonce: airtableConnector.nonce,
                api_key: apiKey,
                base_id: baseId,
                table_name: tableName,
                filters: filters
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    $status.html('<span style="color: green;">Connection successful! ' + 
                                (response.record_count || 0) + ' records found.</span>');
                    
                    // Display data
                    var html = '<div class="airtable-success">' +
                              '<p><strong>Success!</strong> Connection to Airtable is working properly.</p>' +
                              '<p><strong>Records Retrieved:</strong> ' + (response.record_count || 0) + '</p>';
                    
                    // Add filter information if applied
                    if (response.filter_applied) {
                        if (response.filters && response.filters.length > 0) {
                            if (response.filters.length === 1) {
                                html += '<p><strong>Filter Applied:</strong> ' + response.filters[0].field + 
                                       ' = "' + response.filters[0].value + '"</p>';
                            } else {
                                html += '<p><strong>Filters Applied:</strong></p>' +
                                       '<ul class="filter-list">';
                                
                                response.filters.forEach(function(filter) {
                                    html += '<li>' + filter.field + ' = "' + filter.value + '"</li>';
                                });
                                
                                html += '</ul>';
                            }
                            html += '<p><strong>Filtered Records Found:</strong> ' + 
                                   (response.filtered_record_count || 0) + '</p>';
                        }
                    }
                    
                    if (response.url) {
                        html += '<p><strong>API URL:</strong> ' + response.url + '</p>';
                    }
                    
                    html += '</div>';
                    
                    // Add the JSON data
                    if (response.data && response.data.records && response.data.records.length > 0) {
                        html += '<h3>API Response</h3>' +
                                '<div class="api-response-json">' +
                                '<pre>' + JSON.stringify(response.data, null, 2) + '</pre>' +
                                '</div>';
                        
                        // Update the available fields
                        var fields = [];
                        var fieldsHtml = '';
                        
                        // Extract all unique field names from the records
                        response.data.records.forEach(function(record) {
                            if (record.fields) {
                                Object.keys(record.fields).forEach(function(field) {
                                    if (fields.indexOf(field) === -1) {
                                        fields.push(field);
                                    }
                                });
                            }
                        });
                        
                        // Sort fields alphabetically
                        fields.sort();
                        
                        // Create checkboxes for each field
                        if (fields.length > 0) {
                            fields.forEach(function(field) {
                                // Check if the field was previously selected
                                var isChecked = $('#fields-selector input[value="' + field.replace(/"/g, '\\"') + '"]').prop('checked') || false;
                                
                                fieldsHtml += '<label class="field-checkbox">' +
                                             '<input type="checkbox" name="fields_to_display[]" value="' + field.replace(/"/g, '&quot;') + '"' + 
                                             (isChecked ? ' checked' : '') + '>' +
                                             field +
                                             '</label>';
                            });
                            
                            $('#fields-selector').html(fieldsHtml);
                        } else {
                            $('#fields-selector').html('<p class="no-fields-message">No fields found in the response.</p>');
                        }
                        
                        // Show the fields container if it was hidden
                        $('#fields-container').show();
                    } else {
                        html += '<p>No records found in the response.</p>';
                    }
                    
                    $responseContainer.html(html);
                } else {
                    // Show error message
                    $status.html('<span style="color: red;">Error: ' + response.message + '</span>');
                    
                    // Display error details
                    var html = '<div class="airtable-error">' +
                              '<p><strong>Error:</strong> ' + response.message + '</p>';
                    
                    if (response.url) {
                        html += '<p><strong>Request URL:</strong> ' + response.url + '</p>';
                    }
                    
                    html += '</div>';
                    $responseContainer.html(html);
                }
            },
            error: function(xhr, status, error) {
                $status.html('<span style="color: red;">Connection failed. Please check your settings.</span>');
                $responseContainer.html('<div class="airtable-error">' +
                                     '<p><strong>Error:</strong> WordPress AJAX request failed: ' + error + '</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
    
    // Add spinning animation
    $('<style>.dashicons-update { display: inline-block; animation: rotation 2s infinite linear; } @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>').appendTo('head');
});