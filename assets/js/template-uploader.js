/**
 * JavaScript for the HTML template uploader
 */

jQuery(document).ready(function($) {
    // Function to handle file upload preview
    function handleTemplateFilePreview() {
        $('#cgptfc_html_template_file').change(function(e) {
            var file = e.target.files[0];
            if (file) {
                $('.cgptfc-template-filename').text(file.name);
                
                // Add remove button if not exists
                if ($('.cgptfc-remove-template').length === 0) {
                    $('.cgptfc-template-upload-field').append(
                        '<button type="button" class="button cgptfc-remove-template">' + 
                        cgptfc_uploader.strings.remove + 
                        '</button>'
                    );
                }
                
                // Read file contents
                var reader = new FileReader();
                reader.onload = function(event) {
                    $('#cgptfc_html_template').val(event.target.result).show();
                    
                    // Optionally generate a preview
                    generatePreview(event.target.result);
                };
                reader.readAsText(file);
            }
        });
    }
    
    // Function to generate HTML preview
    function generatePreview(html) {
        var preview = $('.cgptfc-preview-container');
        var iframe = $('<iframe>').attr({
            srcdoc: html,
            style: 'width:100%; height:200px; border:none;'
        });
        
        preview.empty().append(iframe);
    }
    
    // Handle removing the template
    $(document).on('click', '.cgptfc-remove-template', function() {
        $('#cgptfc_html_template_file').val('');
        $('#cgptfc_html_template').val('').hide();
        $('.cgptfc-template-filename').text(cgptfc_uploader.strings.no_file);
        $('.cgptfc-preview-container').empty().append('<p>' + cgptfc_uploader.strings.no_file + '</p>');
        $(this).remove();
    });
    
    // Initialize file uploader
    handleTemplateFilePreview();
    
    // Toggle visibility of HTML template fields
    $('#cgptfc_use_html_template').change(function() {
        if ($(this).is(':checked')) {
            $('#html_template_row, #template_instruction_row').show();
        } else {
            $('#html_template_row, #template_instruction_row').hide();
        }
    });
    
    // Preview changes when editing directly in textarea
    $('#cgptfc_html_template').on('change keyup', function() {
        generatePreview($(this).val());
    });
});