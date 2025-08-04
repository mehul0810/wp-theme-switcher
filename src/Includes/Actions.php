<?php
/**
 * Frontend Actions.
 * 
 * @since 1.0.0
 * @package WPThemeSwitcher
 */

namespace WPThemeSwitcher\Includes;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Actions {
	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Add admin bar menu for preview banner (frontend only)
		add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 999 );

		// Add metabox for classic editor only
		add_action( 'add_meta_boxes', array( $this, 'add_theme_metabox' ) );
		add_action( 'save_post', array( $this, 'save_theme_metabox' ) );
	}
	/**
	 * Add Theme Switcher metabox for classic editor only.
	 *
	 * @since 1.0.0
	 */
	public function add_theme_metabox( $post_type ) {
		// Only show for classic editor (not Gutenberg)
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( $post_type ) ) {
			return;
		}

		add_meta_box(
			'wpts_theme_switcher_metabox',
			__( 'WP Theme Switcher', 'wpts-theme-switcher' ),
			array( $this, 'render_theme_metabox' ),
			$post_type,
			'side',
			'default'
		);
	}

	/**
	 * Render the Theme Switcher metabox.
	 *
	 * @since 1.0.0
	 */
	public function render_theme_metabox( $post ) {
		$theme_switcher = new \WPThemeSwitcher\ThemeSwitcher();
		$themes = $theme_switcher->get_available_themes();
		$selected = get_post_meta( $post->ID, 'wpts_theme_switcher_active_theme', true );

		wp_nonce_field( 'wpts_theme_metabox', 'wpts_theme_metabox_nonce' );
		echo '<label for="wpts_selected_theme"><strong>' . esc_html__( 'Select Theme', 'wpts-theme-switcher' ) . '</strong></label><br />';
		echo '<select name="wpts_selected_theme" id="wpts_selected_theme" style="width:100%;">';
		echo '<option value="">' . esc_html__( 'Use Active Theme', 'wpts-theme-switcher' ) . '</option>';
		foreach ( $themes as $theme_slug => $theme_name ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $theme_slug ),
				selected( $selected, $theme_slug, false ),
				esc_html( $theme_name )
			);
		}
		echo '</select>';
	}

	/**
	 * Save the selected theme from the metabox.
	 *
	 * @since 1.0.0
	 */
	public function save_theme_metabox( $post_id ) {
		// Verify nonce
		if ( ! isset( $_POST['wpts_theme_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['wpts_theme_metabox_nonce'], 'wpts_theme_metabox' ) ) {
			return;
		}
		// Autosave?
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Check permissions
		if ( isset( $_POST['post_type'] ) && 'page' === $_POST['post_type'] ) {
			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}
		} else {
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
		}

		if ( isset( $_POST['wpts_selected_theme'] ) ) {
			$theme = sanitize_text_field( $_POST['wpts_selected_theme'] );
			if ( $theme ) {
				update_post_meta( $post_id, 'wpts_theme_switcher_active_theme', $theme );
			} else {
				delete_post_meta( $post_id, 'wpts_theme_switcher_active_theme' );
			}
		}
	}

	/**
	 * Add admin bar menu.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar object.
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		// Only show for users who can preview.
		$theme_switcher = new \WPThemeSwitcher\ThemeSwitcher();
		if ( ! $theme_switcher->can_user_preview() ) {
			return;
		}

		$preview_theme = $theme_switcher->get_preview_theme();
		$query_param = $theme_switcher->get_query_param_name();
		$themes = $theme_switcher->get_available_themes();

		if ( $preview_theme ) {
			$theme = wp_get_theme( $preview_theme );
			// Show previewing theme and exit link
			$wp_admin_bar->add_node( array(
				'id'    => 'wpts-preview',
				'title' => sprintf(
					/* translators: %s: Theme name */
					__( 'Previewing: %s', 'wpts-theme-switcher' ),
					$theme->get( 'Name' )
				),
				'href'  => '#',
				'meta'  => array(
					'class' => 'wpts-preview-node',
				),
			) );
			$current_url = remove_query_arg( $query_param, esc_url( $_SERVER['REQUEST_URI'] ) );
			$wp_admin_bar->add_node( array(
				'id'     => 'wpts-exit-preview',
				'parent' => 'wpts-preview',
				'title'  => __( 'Exit Preview', 'wpts-theme-switcher' ),
				'href'   => $current_url,
				'meta'   => array(
					'class' => 'wpts-exit-preview-link',
				),
			) );
		} else {
			// Show Preview Theme menu by default
			$wp_admin_bar->add_node( array(
				'id'    => 'wpts-preview',
				'title' => __( 'Preview Theme', 'wpts-theme-switcher' ),
				'href'  => '#',
			) );
		}

		$active_theme_slug = get_stylesheet();
		foreach ( $themes as $theme_slug => $theme_name ) {
			$default_active_theme = get_option( 'current_theme' );
			$is_active = ( $theme_name === $default_active_theme );
			$display_name = $theme_name . ( $is_active ? ' ' . __( '[Active Theme]', 'wpts-theme-switcher' ) : '' );
			$wp_admin_bar->add_node( array(
				'id'     => 'wpts-theme-' . sanitize_html_class( $theme_slug ),
				'parent' => 'wpts-preview',
				'title'  => $display_name,
				'href'   => add_query_arg( $query_param, $theme_slug ),
				'meta'   => array(
					'class' => ( $preview_theme && $theme_slug === $preview_theme ) ? 'wpts-current-theme' : '',
				),
			) );
		}
	}
}