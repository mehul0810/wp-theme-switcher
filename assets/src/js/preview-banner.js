import '../css/preview-banner.css';

/**
 * Preview Banner Functionality
 * 
 * @package WPThemeSwitcher
 */

(function($) {
    'use strict';

    /**
     * Initialize preview banner functionality
     */
    function initPreviewBanner() {
        // Add event listener to theme selector in banner
        $('#wpts-theme-select').on('change', function() {
            const selectedTheme = $(this).val();
            
            if (!selectedTheme) {
                return;
            }

            // AJAX call to switch theme or direct redirect
            if (selectedTheme === PreviewBanner.currentTheme) {
                return;
            }

            // Update the URL and reload
            const newUrl = addOrUpdateUrlParam(
                PreviewBanner.currentUrl,
                PreviewBanner.queryParam,
                selectedTheme
            );
            
            window.location.href = newUrl;
        });

        // Make the banner draggable
        if ($.fn.draggable && $('#wpts-preview-banner').length) {
            $('#wpts-preview-banner').draggable({
                axis: 'y',
                containment: 'window',
                handle: '.wpts-preview-banner-inner'
            });
        }

        // Add a close button for the compatibility notice
        if ($('#wpts-compatibility-notice').length) {
            const $noticeDiv = $('#wpts-compatibility-notice');
            const $closeButton = $('<button>', {
                class: 'wpts-notice-close',
                html: '&times;',
                title: 'Dismiss notice'
            });

            $noticeDiv.find('.wpts-notice-inner').append($closeButton);

            $closeButton.on('click', function() {
                $noticeDiv.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        }
    }

    /**
     * Add or update a URL parameter
     * 
     * @param {string} url Current URL
     * @param {string} param Parameter name
     * @param {string} value Parameter value
     * @return {string} Updated URL
     */
    function addOrUpdateUrlParam(url, param, value) {
        const re = new RegExp("([?&])" + param + "=.*?(&|$)", "i");
        const separator = url.indexOf('?') !== -1 ? "&" : "?";
        
        if (url.match(re)) {
            return url.replace(re, '$1' + param + "=" + value + '$2');
        } else {
            return url + separator + param + "=" + value;
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initPreviewBanner();
    });

})(jQuery);