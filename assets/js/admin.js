/**
 * Admin JavaScript for Airtable Connector
 */

// Shortcode clipboard functionality
function initializeShortcodeClipboard() {
    // Handle click on shortcode code elements
    document.querySelectorAll('#shortcode-display code, #shortcode-usage-container code').forEach(function(element) {
        element.addEventListener('click', function() {
            copyToClipboard(this.textContent);
            showCopiedMessage(this.parentNode);
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
    }, 1500);
}

jQuery(document).ready(function($) {
    // Initialize shortcode clipboard functionality
    initializeShortcodeClipboard();
    
    // Initial setup of refresh data link
    $('.refresh-data-link').on('click', function(e) {
        e.preventDefault();
        var $link = $(this);
        
        // Don't do anything if already refreshing
        if ($link.hasClass('refreshing')) {
            return;
        }
        
        // Show refreshing state
        $link.addClass('refreshing');
        
        // Trigger the test connection button
        $('#test-connection').trigger('click');
        
        // Reset state after a timeout
        setTimeout(function() {
            $link.removeClass('refreshing');
        }, 3000);
    });
    
    // Handle test connection button
    $('#test-connection').on('click', function(e) {
        // Clear any existing status messages first
        $('#connection-feedback').remove();
        
        // Original AJAX call will run here, then our modifications will apply
        
        // Set a timeout to modify the response after it's loaded
        setTimeout(function() {
            // Fix the second heading from "API Response" to "DATA" with fetch data link
            var apiResponseHeadings = $('#api-response-content h3');
            
            // Check if the operation was successful
            var isSuccess = $('#api-response-content .airtable-success').length > 0;
            var isError = $('#api-response-content .airtable-error').length > 0;
            
            // Create feedback message for connection area
            var statusHtml = '';
            if (isSuccess) {
                statusHtml = '<span id="connection-feedback" class="status-success">Connection successful!</span>';
            } else if (isError) {
                var errorMsg = $('#api-response-content .airtable-error').text().trim();
                errorMsg = errorMsg.length > 50 ? errorMsg.substring(0, 50) + '...' : errorMsg;
                statusHtml = '<span id="connection-feedback" class="status-error">Error: ' + errorMsg + '</span>';
            }
            
            // Add feedback next to the verify connection button
            if (statusHtml && !$('#connection-feedback').length) {
                $('#test-connection').after(statusHtml);
                
                // Auto-remove after some time
                setTimeout(function() {
                    $('#connection-feedback').fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            if (apiResponseHeadings.length > 0) {
                apiResponseHeadings.each(function(index) {
                    if ($(this).text() === 'API Response') {
                        // Create the refresh link with potential status indicator
                        var refreshHtml = '<span class="refresh-data-link"><span class="dashicons dashicons-update"></span> fetch data</span>';
                        
                        // Add feedback to fetch data area if needed
                        if (isSuccess && !$('.fetch-feedback').length) {
                            refreshHtml += '<span class="fetch-feedback status-success">Data refreshed!</span>';
                        } else if (isError && !$('.fetch-feedback').length) {
                            refreshHtml += '<span class="fetch-feedback status-error">Failed to fetch data</span>';
                        }
                        
                        $(this).html('DATA ' + refreshHtml);
                        
                        // Auto-remove fetch feedback after some time
                        setTimeout(function() {
                            $('.fetch-feedback').fadeOut(500, function() {
                                $(this).remove();
                            });
                        }, 5000);
                        
                        // Reattach the event handler to the new refresh link
                        $(this).find('.refresh-data-link').on('click', function(e) {
                            e.preventDefault();
                            var $link = $(this);
                            
                            // Remove any existing feedback
                            $('.fetch-feedback').remove();
                            
                            // Don't do anything if already refreshing
                            if ($link.hasClass('refreshing')) {
                                return;
                            }
                            
                            // Show refreshing state
                            $link.addClass('refreshing');
                            
                            // Trigger the test connection button
                            $('#test-connection').trigger('click');
                            
                            // Reset state after a timeout
                            setTimeout(function() {
                                $link.removeClass('refreshing');
                            }, 3000);
                        });
                    }
                });
            }
        }, 500); // Wait for the AJAX response to complete
    });
    
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
    
    // Add spinning animation
    $('<style>.dashicons-update { display: inline-block; } @keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>').appendTo('head');
// Add scroll indicator for DATA section
function initializeScrollIndicator() {
    const dataContainer = document.querySelector('.api-response-json');
    if (!dataContainer) return;
    
    // Check if container is scrollable
    const checkScrollable = () => {
        if (dataContainer.scrollHeight > dataContainer.clientHeight) {
            dataContainer.classList.add('scrollable');
        } else {
            dataContainer.classList.remove('scrollable');
        }
    };
    
    // Initial check
    checkScrollable();
    
    // Update on window resize
    window.addEventListener('resize', checkScrollable);
    
    // Update when scrolled to bottom
    dataContainer.addEventListener('scroll', () => {
        if (dataContainer.scrollHeight - dataContainer.scrollTop <= dataContainer.clientHeight + 10) {
            dataContainer.classList.remove('scrollable');
        } else {
            dataContainer.classList.add('scrollable');
        }
    });
}

// Call this function to initialize the scroll indicator
initializeScrollIndicator();

// Also reinitialize after test connection
$('#test-connection').on('click', function() {
    setTimeout(initializeScrollIndicator, 1000);
});

});