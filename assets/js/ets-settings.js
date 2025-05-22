/**
 * Settings Page React Component
 * 
 * @package EasyThemeSwitcher
 */

(function(wp) {
    const { __ } = wp.i18n;
    const { render, useState, useEffect } = wp.element;
    const { 
        Panel, 
        PanelBody, 
        PanelRow, 
        SelectControl, 
        TextControl, 
        ToggleControl, 
        Button, 
        Spinner, 
        Notice, 
        Card, 
        CardHeader, 
        CardBody, 
        CardFooter 
    } = wp.components;

    /**
     * Settings App Component
     */
    const SettingsApp = () => {
        // State variables
        const [settings, setSettings] = useState({
            enable_preview_banner: 'yes',
            default_preview_theme: '',
            preview_query_param: 'ets_theme'
        });
        const [themes, setThemes] = useState([]);
        const [isLoading, setIsLoading] = useState(true);
        const [isSaving, setIsSaving] = useState(false);
        const [notice, setNotice] = useState({ status: '', message: '' });

        // Load settings on component mount
        useEffect(() => {
            loadSettings();
        }, []);

        /**
         * Load settings from the server
         */
        const loadSettings = () => {
            setIsLoading(true);

            const data = new FormData();
            data.append('action', 'ets_get_settings');
            data.append('nonce', etsSettings.nonce);

            fetch(etsSettings.ajaxUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    setSettings(response.data.settings);
                    
                    // Convert themes object to array format for SelectControl
                    const themeOptions = Object.keys(response.data.themes).map(slug => ({
                        value: slug,
                        label: response.data.themes[slug]
                    }));
                    
                    setThemes(themeOptions);
                } else {
                    setNotice({
                        status: 'error',
                        message: response.data.message || etsSettings.strings.error
                    });
                }
                setIsLoading(false);
            })
            .catch(error => {
                console.error('Error loading settings:', error);
                setNotice({
                    status: 'error',
                    message: etsSettings.strings.error
                });
                setIsLoading(false);
            });
        };

        /**
         * Save settings to the server
         */
        const saveSettings = () => {
            setIsSaving(true);
            setNotice({ status: '', message: '' });

            const data = new FormData();
            data.append('action', 'ets_save_settings');
            data.append('nonce', etsSettings.nonce);
            data.append('settings', JSON.stringify(settings));

            fetch(etsSettings.ajaxUrl, {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    setSettings(response.data.settings);
                    setNotice({
                        status: 'success',
                        message: response.data.message || etsSettings.strings.saved
                    });
                } else {
                    setNotice({
                        status: 'error',
                        message: response.data.message || etsSettings.strings.error
                    });
                }
                setIsSaving(false);
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                setNotice({
                    status: 'error',
                    message: etsSettings.strings.error
                });
                setIsSaving(false);
            });
        };

        /**
         * Handle setting changes
         * 
         * @param {string} key Setting key
         * @param {any} value Setting value
         */
        const handleSettingChange = (key, value) => {
            setSettings({
                ...settings,
                [key]: value
            });
        };

        // If still loading, show spinner
        if (isLoading) {
            return (
                <div className="ets-settings-loading">
                    <Spinner />
                    <p>{etsSettings.strings.loading}</p>
                </div>
            );
        }

        return (
            <div className="ets-settings-wrapper">
                {notice.status && (
                    <Notice 
                        status={notice.status} 
                        isDismissible={true}
                        onRemove={() => setNotice({ status: '', message: '' })}
                    >
                        {notice.message}
                    </Notice>
                )}

                <Card>
                    <CardHeader>
                        <h2>{etsSettings.strings.settingsTitle}</h2>
                        <p>{etsSettings.strings.settingsDescription}</p>
                    </CardHeader>
                    
                    <CardBody>
                        <Panel>
                            <PanelBody 
                                title={__('General Settings', 'easy-theme-switcher')}
                                initialOpen={true}
                            >
                                <PanelRow>
                                    <ToggleControl
                                        label={etsSettings.strings.enableBanner}
                                        help={etsSettings.strings.enableBannerHelp}
                                        checked={settings.enable_preview_banner === 'yes'}
                                        onChange={(value) => handleSettingChange('enable_preview_banner', value ? 'yes' : 'no')}
                                    />
                                </PanelRow>
                                
                                <PanelRow>
                                    <SelectControl
                                        label={etsSettings.strings.defaultTheme}
                                        help={etsSettings.strings.defaultThemeHelp}
                                        value={settings.default_preview_theme}
                                        options={[
                                            { value: '', label: etsSettings.strings.selectTheme },
                                            ...themes
                                        ]}
                                        onChange={(value) => handleSettingChange('default_preview_theme', value)}
                                    />
                                </PanelRow>
                                
                                <PanelRow>
                                    <TextControl
                                        label={etsSettings.strings.queryParam}
                                        help={etsSettings.strings.queryParamHelp}
                                        value={settings.preview_query_param}
                                        onChange={(value) => handleSettingChange('preview_query_param', value)}
                                    />
                                </PanelRow>
                            </PanelBody>
                        </Panel>
                    </CardBody>
                    
                    <CardFooter>
                        <Button 
                            isPrimary 
                            onClick={saveSettings}
                            isBusy={isSaving}
                            disabled={isSaving}
                        >
                            {isSaving ? etsSettings.strings.saving : etsSettings.strings.save}
                        </Button>
                    </CardFooter>
                </Card>
            </div>
        );
    };

    // Render the app
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('ets-settings-app');
        if (container) {
            render(<SettingsApp />, container);
        }
    });

})(window.wp);