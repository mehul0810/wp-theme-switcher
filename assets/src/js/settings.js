/**
 * Settings Page React Component
 * 
 * @package SmartThemeSwitcher
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
        PanelRow,
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
            <div className="sts-settings-header">
                <div className="sts-settings-header-left">
                    <div className="sts-plugin-name">{stsSettings.strings.pluginName}</div>
                    <div className="sts-page-title">{stsSettings.strings.settingsTitle}</div>
                </div>
                <div className="sts-settings-header-right">
                    <ExternalLink href={stsSettings.docUrl} className="sts-doc-link">
                        {stsSettings.strings.viewDocs}
                    </ExternalLink>
                    <span className="sts-version-badge">v{stsSettings.version}</span>
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
            <Panel className="sts-post-type-panel">
                <PanelBody 
                    title={postType.label}
                    initialOpen={postTypeSettings.enabled}
                >
                    <div className="sts-panel-header">
                        <div className="sts-panel-header-right">
                            <div className="sts-toggle-dropdown-group">
                                <SelectControl
                                    label={stsSettings.strings.selectTheme}
                                    value={postTypeSettings.theme}
                                    options={[
                                        { label: stsSettings.strings.useActiveTheme, value: 'use_active' },
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
                        </div>
                    </div>
                </PanelBody>
            </Panel>
        );
    };

    /**
     * Taxonomy Panel Component
     */
    const TaxonomyPanel = ({ taxonomy, settings, onSettingChange }) => {
        const taxonomySettings = settings.taxonomies[taxonomy.name] || { enabled: false, theme: 'use_active' };
        
        return (
            <Panel className="sts-taxonomy-panel">
                <PanelBody 
                    title={taxonomy.label}
                    initialOpen={taxonomySettings.enabled}
                >
                    <div className="sts-panel-header">
                        <div className="sts-panel-header-right">
                            <div className="sts-toggle-dropdown-group">
                                <SelectControl
                                    label={stsSettings.strings.selectTheme}
                                    value={taxonomySettings.theme}
                                    options={[
                                        { label: stsSettings.strings.useActiveTheme, value: 'use_active' },
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
                        </div>
                    </div>
                </PanelBody>
            </Panel>
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
            <div className="sts-general-tab">
                <h3>{__('Post Types', 'smart-theme-switcher')}</h3>
                {Object.values(postTypes).map((postType) => (
                    <PostTypePanel 
                        key={postType.name}
                        postType={postType}
                        settings={{ ...settings, post_types: safePostTypes }}
                        onSettingChange={onSettingChange}
                    />
                ))}
                
                <h3>{__('Taxonomies', 'smart-theme-switcher')}</h3>
                {Object.values(taxonomies).map((taxonomy) => (
                    <TaxonomyPanel
                        key={taxonomy.name}
                        taxonomy={taxonomy}
                        settings={{ ...settings, taxonomies: safeTaxonomies }}
                        onSettingChange={onSettingChange}
                    />
                ))}
            </div>
        );
    };

    /**
     * Advanced Tab Component
     */
    const AdvancedTab = ({ settings, onSettingChange }) => {
        const advancedSettings = settings.advanced || { preview_enabled: true, debug_enabled: false };
        
        return (
            <div className="sts-advanced-tab">
                <Card className="sts-advanced-panel">
                    <CardBody>
                        <ToggleControl
                            label={stsSettings.strings.enableThemePreview}
                            checked={advancedSettings.preview_enabled}
                            onChange={(preview_enabled) => {
                                onSettingChange('advanced', {
                                    ...advancedSettings,
                                    preview_enabled
                                });
                            }}
                        />
                        <ToggleControl
                            label={stsSettings.strings.enableDebugging}
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
            preview_query_param: 'sts_theme'
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
                fetch(`${stsSettings.restUrl}/settings`, {
                    headers: { 'X-WP-Nonce': stsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${stsSettings.restUrl}/post-types`, {
                    headers: { 'X-WP-Nonce': stsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${stsSettings.restUrl}/taxonomies`, {
                    headers: { 'X-WP-Nonce': stsSettings.nonce }
                }).then(response => response.json()),
                fetch(`${stsSettings.restUrl}/themes`, {
                    headers: { 'X-WP-Nonce': stsSettings.nonce }
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
                    message: stsSettings.strings.error
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

            fetch(`${stsSettings.restUrl}/settings`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': stsSettings.nonce
                },
                body: JSON.stringify(settings)
            })
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    setNotice({
                        status: 'success',
                        message: response.message || stsSettings.strings.success
                    });
                } else {
                    setNotice({
                        status: 'error',
                        message: response.message || stsSettings.strings.error
                    });
                }
                setIsSaving(false);
            })
            .catch(error => {
                console.error('Error saving settings:', error);
                setNotice({
                    status: 'error',
                    message: stsSettings.strings.error
                });
                setIsSaving(false);
            });
        };

        // If still loading, show spinner
        if (isLoading) {
            return (
                <div className="sts-settings-app">
                    <Header />
                    <div className="sts-settings-content">
                        <Placeholder className="sts-settings-loading">
                            <Spinner />
                            <p>{stsSettings.strings.loading}</p>
                        </Placeholder>
                    </div>
                </div>
            );
        }

        return (
            <div className="sts-settings-app">
                <Header />
                
                <div className="sts-settings-content">
                    {notice.status && (
                        <div className={`notice notice-${notice.status} is-dismissible`}>
                            <p>{notice.message}</p>
                        </div>
                    )}
                    
                    <div className="sts-tabs-container">
                        <TabPanel
                            className="sts-settings-tabs"
                            activeClass="is-active"
                            initialTabName={activeTab}
                            onSelect={(tabName) => setActiveTab(tabName)}
                            tabs={[
                                {
                                    name: 'general',
                                    title: stsSettings.strings.generalTab,
                                    className: 'sts-tab-general',
                                },
                                {
                                    name: 'advanced',
                                    title: stsSettings.strings.advancedTab,
                                    className: 'sts-tab-advanced',
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
                    
                    <div className="sts-settings-footer">
                        <Button 
                            isPrimary 
                            onClick={saveSettings}
                            isBusy={isSaving}
                            disabled={isSaving}
                        >
                            {isSaving ? stsSettings.strings.saving : stsSettings.strings.save}
                        </Button>
                    </div>
                </div>
            </div>
        );
    };

    // Render the app
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('sts-settings-app');
        if (container) {
            render(<SettingsApp />, container);
        }
    });

})(window.wp);