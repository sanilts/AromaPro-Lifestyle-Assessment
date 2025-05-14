/**
 * JavaScript for Response Logs UI in ChatGPT & Gemini Fluent Forms Connector
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
    
    // Copy response to clipboard
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
    
    // Initialize filters
    $('.cgptfc-filter-select').on('change', function() {
        $(this).closest('form').submit();
    });
});