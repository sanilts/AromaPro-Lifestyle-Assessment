/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Other/javascript.js to edit this template
 */


function showLightbox() {
    document.getElementById('single-tegel-lightbox').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('single-tegel-lightbox').style.display = 'none';
}


jQuery(document).ready(function($) {
    // Initialize color picker
    $('.color-picker').wpColorPicker();

    // Icon type selector
    $('#term_icon_type').on('change', function() {
        const selectedType = $(this).val();
        $('.icon-type-field').hide();
        
        if (selectedType === 'dashicon') {
            $('#dashicon-selector').show();
        } else if (selectedType === 'image') {
            $('#image-selector').show();
        }
    });

    // Image uploader
    $('.upload-image-button').click(function(e) {
        e.preventDefault();
        
        const button = $(this);
        const imageContainer = button.closest('.custom-image-container');
        const imageInput = imageContainer.find('#term_icon_image');
        const imagePreview = imageContainer.find('.image-preview');
        const removeButton = imageContainer.find('.remove-image-button');

        const frame = wp.media({
            title: taxonomyIconAdmin.mediaTitle,
            button: {
                text: taxonomyIconAdmin.mediaBtnText
            },
            multiple: false
        });

        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            imageInput.val(attachment.url);
            imagePreview.attr('src', attachment.url).show();
            removeButton.show();
        });

        frame.open();
    });

    // Remove image
    $('.remove-image-button').click(function(e) {
        e.preventDefault();
        
        const button = $(this);
        const imageContainer = button.closest('.custom-image-container');
        const imageInput = imageContainer.find('#term_icon_image');
        const imagePreview = imageContainer.find('.image-preview');

        imageInput.val('');
        imagePreview.attr('src', '').hide();
        button.hide();
    });

    // Initialize Dashicons Picker
    if (typeof $.fn.dashiconsPicker !== 'undefined') {
        $('.dashicons-picker').dashiconsPicker({
            onClick: function(iconClass) {
                const preview = $(this).closest('.dashicons-picker-container').find('.dashicon-preview');
                preview.html(`<span class="dashicons ${iconClass}"></span>`);
            }
        });
    }
});