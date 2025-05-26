/**
 * Theme Switcher Block Editor Component
 * 
 * @package SmartThemeSwitcher
 */

import '../css/ets-editor.css';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { useSelect, useDispatch } from '@wordpress/data';
import { SelectControl } from '@wordpress/components';
import { useState, useEffect } from '@wordpress/element';

(function( wp ) {
	const { __ } = wp.i18n;

	/**
	 * Theme Switcher Meta Field Component
	 */
	const ThemeMetaField = () => {
		// Get all themes from localized data
		const availableThemes = (window.etsEditor && window.etsEditor.themes) || [];
		// Get meta value
		const meta = useSelect((select) => select('core/editor').getEditedPostAttribute('meta') || {}, []);
		const { editPost } = useDispatch('core/editor');
		const metaKey = 'smart_theme_switcher_active_theme';
		const currentValue = meta[metaKey] || '';
		const [selectedTheme, setSelectedTheme] = useState(currentValue);

		useEffect(() => {
			setSelectedTheme(currentValue);
		}, [currentValue]);

		const handleChange = (value) => {
			setSelectedTheme(value);
			editPost({ meta: { ...meta, [metaKey]: value } });
		};

		return (
				<SelectControl
					label={__('Select a theme', 'smart-theme-switcher')}
					value={selectedTheme}
					options={[
						{ label: __('Use Active Theme', 'smart-theme-switcher'), value: '' },
						...availableThemes
					]}
					onChange={handleChange}
				/>
		);
	};

	const ThemeMetaFieldSlot = () => {
		// Insert in Document Settings panel
		return <PluginDocumentSettingPanel name="smart-theme-switcher" title={__('Smart Theme Switcher', 'smart-theme-switcher')} icon="admin-appearance"><ThemeMetaField /></PluginDocumentSettingPanel>;
	};

	// Register the plugin
	registerPlugin('smart-theme-switcher-theme-meta', {
		render: ThemeMetaFieldSlot,
		icon: 'admin-appearance',
	});

})( window.wp );