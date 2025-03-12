/**
 * Post Type Slider JavaScript
 * 
 * This file should be placed in /js/post-type-slider.js
 */

(function($) {
    'use strict';
    
    // Check if required dependencies are available
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded');
        return;
    }

    if (typeof $.fn.slick === 'undefined') {
        console.error('Slick slider is not loaded');
        return;
    }
    
    console.log('Post Type Slider JS loaded');
    
    /**
     * Initialize all sliders
     */
    function initializeSliders() {
        $('.pts-post-type-slider').each(function() {
            var $slider = $(this);
            
            // Skip if already initialized
            if ($slider.hasClass('slick-initialized')) {
                try {
                    $slider.slick('unslick');
                } catch(e) {
                    console.error('Error unslicking slider:', e);
                }
            }
            
            // Get slider settings from data attribute
            var settings = $slider.data('settings') || {};
            
            // Default settings
            var defaultSettings = {
                slidesToShow: 3,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 3000,
                infinite: true,
                arrows: true,
                dots: false,
                adaptiveHeight: true,
                prevArrow: '<button type="button" class="slick-prev"><i class="fas fa-chevron-left"></i></button>',
                nextArrow: '<button type="button" class="slick-next"><i class="fas fa-chevron-right"></i></button>',
                responsive: [
                    {
                        breakpoint: 1024,
                        settings: {
                            slidesToShow: 2,
                            slidesToScroll: 1
                        }
                    },
                    {
                        breakpoint: 768,
                        settings: {
                            slidesToShow: 1,
                            slidesToScroll: 1
                        }
                    }
                ]
            };
            
            // Merge default settings with custom settings
            var slickSettings = $.extend({}, defaultSettings, settings);
            
            // Initialize slider with try/catch for error handling
            try {
                console.log('Initializing slider with settings:', slickSettings);
                $slider.slick(slickSettings);
                console.log('Slider initialized successfully');
            } catch (error) {
                console.error('Error initializing slider:', error);
                
                // Try a more basic initialization as fallback
                try {
                    $slider.slick({
                        slidesToShow: 3,
                        arrows: true,
                        dots: false,
                        responsive: [
                            {
                                breakpoint: 768,
                                settings: {
                                    slidesToShow: 1
                                }
                            }
                        ]
                    });
                    console.log('Slider initialized with fallback settings');
                } catch (fallbackError) {
                    console.error('Fallback initialization also failed:', fallbackError);
                }
            }
        });
    }

    // Initialize sliders when document is ready
    $(document).ready(function() {
        console.log('Document ready, preparing to initialize sliders...');
        
        // Slight delay to ensure everything is loaded
        setTimeout(initializeSliders, 500);
    });

    // Re-initialize sliders when Elementor frontend is initialized
    $(window).on('elementor/frontend/init', function() {
        elementorFrontend.hooks.addAction('frontend/element_ready/post_type_slider.default', function($scope) {
            console.log('Elementor frontend ready action triggered');
            setTimeout(initializeSliders, 500);
        });
    });
    
    // Re-initialize on tab change
    $(document).on('click', '.elementor-tab-title', function() {
        console.log('Tab changed, updating slider positions');
        setTimeout(function() {
            $('.pts-post-type-slider').each(function() {
                if ($(this).hasClass('slick-initialized')) {
                    $(this).slick('setPosition');
                }
            });
        }, 100);
    });
    
    // Re-initialize on window resize
    var resizeTimer;
    $(window).on('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            console.log('Window resized, updating slider positions');
            $('.pts-post-type-slider').each(function() {
                if ($(this).hasClass('slick-initialized')) {
                    $(this).slick('setPosition');
                }
            });
        }, 250);
    });
    
    // Fallback initialization function (can be called directly if needed)
    window.initPTSliders = initializeSliders;
    
})(jQuery);