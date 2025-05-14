/**
 * JavaScript for Enhanced Logging UI
 */
jQuery(document).ready(function($) {
    // Toggle between raw and rendered response view
    $('.cgptfc-view-toggle').on('click', function(e) {
        e.preventDefault();
        
        var target = $(this).data('target');
        
        // Toggle active class on tabs
        $('.cgptfc-view-toggle').removeClass('active');
        $(this).addClass('active');
        
        // Toggle visibility of content
        $('.cgptfc-response-view').hide();
        $('#' + target).show();
    });
    
    // Initialize log details page with tab functionality
    if ($('.cgptfc-response-tabs').length > 0) {
        // Show the raw response by default
        $('.cgptfc-response-view').hide();
        $('#cgptfc-raw-response').show();
        $('.cgptfc-view-toggle[data-target="cgptfc-raw-response"]').addClass('active');
    }
    
    // Filter dropdown change handler
    $('.cgptfc-filter-select').on('change', function() {
        // Submit the form when a filter changes
        $(this).closest('form').submit();
    });
    
    // Initialize date pickers
    if ($.datepicker) {
        $('.cgptfc-date-filter').datepicker({
            dateFormat: 'yy-mm-dd',
            changeMonth: true,
            changeYear: true
        });
    }
    
    // Copy response to clipboard button
    $('.cgptfc-copy-response').on('click', function(e) {
        e.preventDefault();
        
        var responseText = $('#cgptfc-raw-response pre').text();
        
        // Create a temporary textarea element to copy from
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(responseText).select();
        
        // Execute copy command
        document.execCommand('copy');
        
        // Remove temporary element
        $temp.remove();
        
        // Show copied message
        var $button = $(this);
        var originalText = $button.text();
        
        $button.text('Copied!');
        
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });
});