import '../css/ets-preview-banner.css';

/**
 * Preview Banner Functionality
 * 
 * @package SmartThemeSwitcher
 */

(function($) {
    'use strict';

    /**
     * Initialize preview banner functionality
     */
    function initPreviewBanner() {
        // Add event listener to theme selector in banner
        $('#ets-theme-select').on('change', function() {
            const selectedTheme = $(this).val();
            
            if (!selectedTheme) {
                return;
            }

            // AJAX call to switch theme or direct redirect
            if (selectedTheme === etsPreviewBanner.currentTheme) {
                return;
            }

            // Update the URL and reload
            const newUrl = addOrUpdateUrlParam(
                etsPreviewBanner.currentUrl,
                etsPreviewBanner.queryParam,
                selectedTheme
            );
            
            window.location.href = newUrl;
        });

        // Make the banner draggable
        if ($.fn.draggable && $('#ets-preview-banner').length) {
            $('#ets-preview-banner').draggable({
                axis: 'y',
                containment: 'window',
                handle: '.ets-preview-banner-inner'
            });
        }

        // Add a close button for the compatibility notice
        if ($('#ets-compatibility-notice').length) {
            const $noticeDiv = $('#ets-compatibility-notice');
            const $closeButton = $('<button>', {
                class: 'ets-notice-close',
                html: '&times;',
                title: 'Dismiss notice'
            });

            $noticeDiv.find('.ets-notice-inner').append($closeButton);

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