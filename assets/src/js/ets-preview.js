import '../css/ets-preview.css';

/**
 * Frontend Preview Functionality
 * 
 * @package SmartThemeSwitcher
 */

(function($) {
    'use strict';

    // Check if we're in preview mode
    if (!etsPreview || !etsPreview.isPreviewMode) {
        return;
    }

    /**
     * Initialize preview functionality
     */
    function initPreview() {
        // Add event listener to theme selector in banner
        $('#ets-theme-select').on('change', function() {
            const selectedTheme = $(this).val();
            
            if (!selectedTheme) {
                return;
            }
            
            // Redirect to the current page with the selected theme
            const currentUrl = window.location.href;
            const newUrl = updateQueryParameter(currentUrl, etsPreview.queryParam, selectedTheme);
            window.location.href = newUrl;
        });
    }

    /**
     * Update a query parameter in a URL
     * 
     * @param {string} uri Current URL
     * @param {string} key Parameter key
     * @param {string} value Parameter value
     * @return {string} Updated URL
     */
    function updateQueryParameter(uri, key, value) {
        const re = new RegExp("([?&])" + key + "=.*?(&|$)", "i");
        const separator = uri.indexOf('?') !== -1 ? "&" : "?";
        
        if (uri.match(re)) {
            return uri.replace(re, '$1' + key + "=" + value + '$2');
        } else {
            return uri + separator + key + "=" + value;
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        initPreview();
    });

})(jQuery);