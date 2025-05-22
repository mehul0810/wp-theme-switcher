/**
 * Theme Switcher Block Editor Component
 * 
 * @package SmartThemeSwitcher
 */

(function( wp ) {
	const { __ } = wp.i18n;
	const { registerPlugin } = wp.plugins;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { Panel, PanelBody, PanelRow, SelectControl, Button } = wp.components;
	const { useState, useEffect } = wp.element;

	/**
	 * Theme Switcher Sidebar Component
	 */
	const ThemeSwitcherSidebar = () => {
		// Set up state
		const [selectedTheme, setSelectedTheme] = useState('');
		const [currentPreviewTheme, setCurrentPreviewTheme] = useState('');
		const [isLoading, setIsLoading] = useState(false);

		// Get the themes from localized data
		const availableThemes = etsEditor.themes || [];
		const activeTheme = etsEditor.activeTheme || '';
		const queryParam = etsEditor.queryParam || 'ets_theme';
		const previewUrl = etsEditor.currentUrl || '';

		// Effect to parse URL for current theme preview
		useEffect(() => {
			// Check if URL contains the theme parameter
			const urlParams = new URLSearchParams(window.location.search);
			const themeParam = urlParams.get(queryParam);
			
			if (themeParam) {
				setCurrentPreviewTheme(themeParam);
				setSelectedTheme(themeParam);
			}
		}, []);

		/**
		 * Handle theme change
		 * 
		 * @param {string} themeSlug The selected theme slug
		 */
		const handleThemeChange = (themeSlug) => {
			setSelectedTheme(themeSlug);
		};

		/**
		 * Apply the selected theme preview
		 */
		const applyThemePreview = () => {
			if (!selectedTheme || isLoading) {
				return;
			}

			setIsLoading(true);

			// Create the preview URL with the theme parameter
			const previewWithTheme = new URL(previewUrl);
			previewWithTheme.searchParams.set(queryParam, selectedTheme);
			
			// Reload the editor with the new theme preview
			window.location.href = previewWithTheme.toString();
		};

		/**
		 * Exit preview mode
		 */
		const exitPreviewMode = () => {
			if (isLoading) {
				return;
			}

			setIsLoading(true);

			// Create the URL without the theme parameter
			const previewWithoutTheme = new URL(previewUrl);
			previewWithoutTheme.searchParams.delete(queryParam);
			
			// Reload the editor without the theme preview
			window.location.href = previewWithoutTheme.toString();
		};

		// Get the preview label for the sidebar
		const getPreviewLabel = () => {
			if (currentPreviewTheme) {
				const currentThemeName = availableThemes.find(theme => theme.value === currentPreviewTheme)?.label || currentPreviewTheme;
				return __('Currently Previewing', 'easy-theme-switcher') + ': ' + currentThemeName;
			}
			return __('Select a theme to preview', 'easy-theme-switcher');
		};

		return (
			<>
				<PluginSidebarMoreMenuItem
					target="easy-theme-switcher-sidebar"
					icon="admin-appearance"
				>
					{ __('Theme Preview', 'easy-theme-switcher') }
				</PluginSidebarMoreMenuItem>
				<PluginSidebar
					name="easy-theme-switcher-sidebar"
					title={ __('Theme Preview', 'easy-theme-switcher') }
					icon="admin-appearance"
				>
					<Panel>
						<PanelBody
							title={ __('Theme Preview', 'easy-theme-switcher') }
							initialOpen={ true }
						>
							<PanelRow>
								<p className="ets-preview-label">{ getPreviewLabel() }</p>
							</PanelRow>
							
							<PanelRow>
								<SelectControl
									label={ __('Select Theme', 'easy-theme-switcher') }
									value={ selectedTheme }
									options={ [
										{ value: '', label: __('Select a theme...', 'easy-theme-switcher') },
										...availableThemes
									] }
									onChange={ handleThemeChange }
								/>
							</PanelRow>

							<PanelRow>
								<Button
									isPrimary
									disabled={ !selectedTheme || selectedTheme === currentPreviewTheme || isLoading }
									onClick={ applyThemePreview }
									isBusy={ isLoading }
								>
									{ isLoading ? __('Loading...', 'easy-theme-switcher') : __('Apply Theme Preview', 'easy-theme-switcher') }
								</Button>
							</PanelRow>

							{currentPreviewTheme && (
								<PanelRow>
									<Button
										isSecondary
										onClick={ exitPreviewMode }
										disabled={ isLoading }
									>
										{ __('Exit Preview Mode', 'easy-theme-switcher') }
									</Button>
								</PanelRow>
							)}

							<PanelRow>
								<p className="ets-preview-info">
									{ __('Preview any installed theme without affecting your live site. Only logged-in users with appropriate permissions will see the preview.', 'easy-theme-switcher') }
								</p>
							</PanelRow>
						</PanelBody>
					</Panel>
				</PluginSidebar>
			</>
		);
	};

	// Register the plugin
	registerPlugin('easy-theme-switcher', {
		render: ThemeSwitcherSidebar,
		icon: 'admin-appearance'
	});

})( window.wp );