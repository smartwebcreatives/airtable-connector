/**
 * Frontend JavaScript for Airtable Connector
 */
(function($) {
    // Format time as MM:SS
    function formatTime(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = seconds % 60;
        return (minutes < 10 ? '0' : '') + minutes + ':' + (remainingSeconds < 10 ? '0' : '') + remainingSeconds;
    }
    
    // Start countdown timer for a specific shortcode instance
    window.startCountdown = function(shortcodeId, totalSeconds) {
        const timerElement = document.querySelector('#' + shortcodeId + ' .airtable-timer');
        if (!timerElement) return;
        
        let seconds = totalSeconds;
        
        // Update timer immediately
        timerElement.textContent = formatTime(seconds);
        
        // Update timer every second
        const interval = setInterval(function() {
            seconds--;
            
            if (seconds <= 0) {
                clearInterval(interval);
                timerElement.textContent = "Refreshing...";
                return;
            }
            
            timerElement.textContent = formatTime(seconds);
        }, 1000);
        
        // Store interval ID on the element
        timerElement.dataset.intervalId = interval;
    };
    
    $(document).ready(function() {
        // Handle "Update Now" button clicks
        $('.airtable-update-now').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const shortcodeId = button.data('shortcode-id');
            const baseId = button.data('base-id');
            const tableName = button.data('table-name');
            const filterField = button.data('filter-field');
            const filterValue = button.data('filter-value');
            
            // Disable button and show loading state
            button.prop('disabled', true).text('Updating...');
            
            // Make AJAX request to update data
            $.ajax({
                url: airtableConnector.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'airtable_update_now',
                    nonce: airtableConnector.nonce,
                    shortcode_id: shortcodeId,
                    base_id: baseId,
                    table_name: tableName,
                    filter_field: filterField,
                    filter_value: filterValue
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show fresh data
                        location.reload();
                    } else {
                        // Show error and re-enable button
                        alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                        button.prop('disabled', false).text('Update Now');
                    }
                },
                error: function() {
                    // Show error and re-enable button
                    alert('Connection error. Please try again.');
                    button.prop('disabled', false).text('Update Now');
                }
            });
        });
        
        // Initialize countdown timers
        $('.airtable-timer').each(function() {
            const $timer = $(this);
            const shortcodeId = $timer.closest('.airtable-connector-container').data('shortcode-id');
            const interval = parseInt($timer.data('interval'), 10);
            
            if (shortcodeId && interval) {
                startCountdown(shortcodeId, interval);
            }
        });
    });
})(jQuery);