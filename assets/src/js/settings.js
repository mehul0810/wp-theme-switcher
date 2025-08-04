/**
 * Settings Page React Component
 * 
 * @package WPThemeSwitcher
 */

import '../css/settings.css';

(function (wp) {
    const { __ } = wp.i18n;
    const { render, useState, useEffect } = wp.element;
    const { 
        Button,
        Card,
        CardBody,
        CardFooter,
        CardHeader,
        ExternalLink,
        Flex,
        FlexItem,
        FlexBlock,
        Panel,
        PanelBody,
        Placeholder,
        SelectControl,
        Spinner,
        TabPanel,
        TextControl,
        ToggleControl,
    } = wp.components;
    const { apiFetch } = wp.apiFetch;

    /**
     * Header Component
     */
    const Header = () => {
        return (
            <div className="wpts-settings-header">
                <div className="wpts-settings-header-left">
                    <div className="wpts-plugin-name">
                        <img className="wpts-plugin-logo" src={wptsSettings.strings.pluginLogo} alt={wptsSettings.strings.pluginName} />
                    </div>
                </div>
                <div className="wpts-settings-header-right">
                    <ExternalLink href={wptsSettings.docUrl} className="wpts-doc-link">
                        {wptsSettings.strings.viewDocs}
                    </ExternalLink>
                    <span className="wpts-version-badge">v{wptsSettings.version}</span>
                </div>
            </div>
        );
    };

    /**
     * Post Type Panel Component
     */
    const PostTypePanel = ({ postType, settings, onSettingChange }) => {
        const postTypeSettings = settings.post_types[postType.name] || { enabled: false, theme: 'use_active' };
        
        return (
            <div className="wpts-toggle-dropdown-group">
                <SelectControl
                    label={postType.label}
                    value={postTypeSettings.theme}
                    options={[
                        { label: wptsSettings.strings.useActiveTheme, value: 'use_active' },
                        ...Object.entries(settings.themes || {})
                            .filter(([key]) => key !== 'use_active')
                            .map(([value, label]) => ({
                                label,
                                value
                            }))
                    ]}
                    onChange={(theme) => {
                        onSettingChange('post_types', {
                            ...settings.post_types,
                            [postType.name]: {
                                ...postTypeSettings,
                                theme
                            }
                        });
                    }}
                />
            </div>
        );
    };

    /**
     * Taxonomy Panel Component
     */
    const TaxonomyPanel = ({ taxonomy, settings, onSettingChange }) => {
        const taxonomySettings = settings.taxonomies[taxonomy.name] || { enabled: false, theme: 'use_active' };

        return (
            <div className="wpts-toggle-dropdown-group">
                <SelectControl
                    label={taxonomy.label}
                    value={taxonomySettings.theme}
                    options={[
                        { label: wptsSettings.strings.useActiveTheme, value: 'use_active' },
                        ...Object.entries(settings.themes || {})
                            .filter(([key]) => key !== 'use_active')
                            .map(([value, label]) => ({
                                label,
                                value
                            }))
                    ]}
                    onChange={(theme) => {
                        onSettingChange('taxonomies', {
                            ...settings.taxonomies,
                            [taxonomy.name]: {
                                ...taxonomySettings,
                                theme
                            }
                        });
                    }}
                />
            </div>
        );
    };

    /**
     * General Tab Component
     */
    const GeneralTab = ({ settings, postTypes, taxonomies, onSettingChange }) => {
        // Defensive: Ensure settings.post_types and settings.taxonomies are always objects
        const safePostTypes = (settings && typeof settings.post_types === 'object' && settings.post_types !== null) ? settings.post_types : {};
        const safeTaxonomies = (settings && typeof settings.taxonomies === 'object' && settings.taxonomies !== null) ? settings.taxonomies : {};

        return (
            <div className="wpts-general-tab">
                <Card size="small" className="wpts-post-type-card">
                    <CardHeader>
                        <Flex direction="column">
                            <h3 className="wpts-card-title">{__('Post Types', 'wpts-theme-switcher')}</h3>
                            <p className="wpts-card-description">
                                {__('Select themes for different post types. Use "Use Active Theme" to apply the currently active theme.', 'wpts-theme-switcher')}
                            </p>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                    {Object.values(postTypes).map((postType) => (
                        <PostTypePanel 
                            key={postType.name}
                            postType={postType}
                            settings={{ ...settings, post_types: safePostTypes }}
                            onSettingChange={onSettingChange}
                        />
                    ))}
                    </CardBody>
                </Card>

                <Card size="small" className="wpts-post-type-card">
                    <CardHeader>
                        <Flex direction="column">
                            <h3 className="wpts-card-title">
                                {__('Taxonomies', 'wpts-theme-switcher')}
                            </h3>
                            <p className="wpts-card-description">
                                {__('Select themes for different post types. Use "Use Active Theme" to apply the currently active theme.', 'wpts-theme-switcher')}
                            </p>
                        </Flex>
                    </CardHeader>
                    <CardBody>
                        {Object.values(taxonomies).map((taxonomy) => (
                            <TaxonomyPanel
                                key={taxonomy.name}
                                taxonomy={taxonomy}
                                settings={{ ...settings, taxonomies: safeTaxonomies }}
                                onSettingChange={onSettingChange}
                            />
                        ))}
                    </CardBody>
                </Card>
            </div>
        );
    };

    /**
     * Advanced Tab Component
     */
    const AdvancedTab = ({ settings, onSettingChange }) => {
        const advancedSettings = settings.advanced || { preview_enabled: true, debug_enabled: false };
        
        return (
            <div className="wpts-advanced-tab">
                <Card className="wpts-advanced-panel">
                    <CardBody>
                        <ToggleControl
                            label={wptsSettings.strings.enableThemePreview}
                            checked={advancedSettings.preview_enabled}
                            onChange={(preview_enabled) => {
                                onSettingChange('advanced', {
                                    ...advancedSettings,
                                    preview_enabled
                                });
                            }}
                        />
                        <ToggleControl
                            label={wptsSettings.strings.enableDebugging}
                            checked={advancedSettings.debug_enabled}
                            onChange={(debug_enabled) => {
                                onSettingChange('advanced', {
                                    ...advancedSettings,
                                    debug_enabled
                                });
                            }}
                        />
                    </CardBody>
                </Card>
            </div>
        );
    };

    /**
     * Main Settings App Component
     */
    const SettingsApp = () => {
        // State variables
        const [settings, setSettings] = useState({
            post_types: {},
            taxonomies: {},
            advanced: {
                preview_enabled: true,
                debug_enabled: false
            },
            themes: {},
            // Legacy settings for backward compatibility
            enable_preview_banner: 'yes',
            default_preview_theme: '',
            preview_query_param: 'wpts_theme'
        });
        
        const [postTypes, setPostTypes] = useState({});
        const [taxonomies, setTaxonomies] = useState({});
        const [isLoading, setIsLoading] = useState(true);
        const [isSaving, setIsSaving] = useState(false);
        const [notice, setNotice] = useState({ status: '', message: '' });
        const [activeTab, setActiveTab] = useState(() => {
            // Check URL for tab param on load (case-insensitive)
            const params = new URLSearchParams(window.location.search);
            const tabParam = params.get('tab');
            if (tabParam && (tabParam.toLowerCase() === 'advanced' || tabParam.toLowerCase() === 'general')) {
                return tabParam.toLowerCase();
            }
            return 'general';
        });

        // Load settings and data on component mount
        useEffect(() => {
            Promise.all([
                fetch(`${wptsSettings.restUrl}/settings`, {
                    headers: { 'X-WP-Nonce': wptsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${wptsSettings.restUrl}/post-types`, {
                    headers: { 'X-WP-Nonce': wptsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${wptsSettings.restUrl}/taxonomies`, {
                    headers: { 'X-WP-Nonce': wptsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${wptsSettings.restUrl}/themes`, {
                    headers: { 'X-WP-Nonce': wptsSettings.nonce }
                }).then(response => response.json())
            ])
            .then(([settingsData, postTypesData, taxonomiesData, themesData]) => {
                setSettings({
                    ...settingsData,
                    themes: themesData
                });
                setPostTypes(postTypesData);
                setTaxonomies(taxonomiesData);
                setIsLoading(false);
                // Set activeTab from URL after loading (fixes tab reset issue)
                const params = new URLSearchParams(window.location.search);
                const tabParam = params.get('tab');
                if (tabParam && (tabParam.toLowerCase() === 'advanced' || tabParam.toLowerCase() === 'general')) {
                    setActiveTab(tabParam.toLowerCase());
                }
            })
            .catch(error => {
                console.error('Error loading data:', error);
                setNotice({
                    status: 'error',
                    message: wptsSettings.strings.error
                });
                setIsLoading(false);
            });
        }, []);

        useEffect(() => {
            // Update URL when activeTab changes
            const params = new URLSearchParams(window.location.search);
            if (params.get('tab') !== activeTab) {
                params.set('tab', activeTab);
                const newUrl = `${window.location.pathname}?${params.toString()}`;
                window.history.replaceState({}, '', newUrl);
            }
        }, [activeTab]);

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

        /**
         * Save settings
         */
        const saveSettings = () => {
            setIsSaving(true);
            setNotice({ status: '', message: '' });

            fetch(`${wptsSettings.restUrl}/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': wptsSettings.nonce
                },
                body: JSON.stringify(settings)
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    setNotice({
                        status: 'success',
                        message: response.message || wptsSettings.strings.success
                    });
                } else {
                    setNotice({
                        status: 'error',
                        message: response.message || wptsSettings.strings.error
                    });
                }
                setIsSaving(false);
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                setNotice({
                    status: 'error',
                    message: wptsSettings.strings.error
                });
                setIsSaving(false);
            });
        };

        // If still loading, show spinner
        if (isLoading) {
            return (
                <div className="wpts-settings-app">
                    <Header />
                    <div className="wpts-settings-content">
                        <Placeholder className="wpts-settings-loading">
                            <Spinner />
                            <p>{wptsSettings.strings.loading}</p>
                        </Placeholder>
                    </div>
                </div>
            );
        }

        return (
            <div className="wpts-settings-app">
                <Header />
                
                <div className="wpts-settings-content">
                    {notice.status && (
                        <div className={`notice notice-${notice.status} is-dismissible`}>
                            <p>{notice.message}</p>
                        </div>
                    )}
                    
                    <div className="wpts-tabs-container">
                        <TabPanel
                            className="wpts-settings-tabs"
                            activeClass="is-active"
                            initialTabName={activeTab}
                            onSelect={(tabName) => setActiveTab(tabName)}
                            tabs={[
                                {
                                    name: 'general',
                                    title: wptsSettings.strings.generalTab,
                                    className: 'wpts-tab-general',
                                },
                                {
                                    name: 'advanced',
                                    title: wptsSettings.strings.advancedTab,
                                    className: 'wpts-tab-advanced',
                                }
                            ]}
                        >
                            {(tab) => {
                                if (tab.name === 'general') {
                                    return (
                                        <GeneralTab 
                                            settings={settings}
                                            postTypes={postTypes}
                                            taxonomies={taxonomies}
                                            onSettingChange={handleSettingChange}
                                        />
                                    );
                                }
                                
                                if (tab.name === 'advanced') {
                                    return (
                                        <AdvancedTab 
                                            settings={settings}
                                            onSettingChange={handleSettingChange}
                                        />
                                    );
                                }
                            }}
                        </TabPanel>
                    </div>
                    
                    <div className="wpts-settings-footer">
                        <Button 
                            isPrimary 
                            onClick={saveSettings}
                            isBusy={isSaving}
                            disabled={isSaving}
                        >
                            {isSaving ? wptsSettings.strings.saving : wptsSettings.strings.save}
                        </Button>
                    </div>
                </div>
            </div>
        );
    };

    // Render the app
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('wpts-settings-app');
        if (container) {
            render(<SettingsApp />, container);
        }
    });

})(window.wp);