<?php
/**
 * Preview Banner Class
 *
 * @package SmartThemeSwitcher
 * @since 1.0.0
 */

namespace SmartThemeSwitcher\Frontend;

use SmartThemeSwitcher\ThemeSwitcher;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * PreviewBanner Class.
 *
 * Handles the preview banner functionality.
 *
 * @since 1.0.0
 */
class PreviewBanner {

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// Initialize hooks.
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	private function init_hooks() {
		// Only register hooks if preview mode is enabled in settings
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$preview_enabled = isset( $settings['enable_preview'] ) && $settings['enable_preview'] === 'yes';
		
		if ( ! $preview_enabled ) {
			return;
		}
		
		// Enqueue preview banner scripts and styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		
		// Output preview banner in footer
		add_action( 'wp_footer', array( $this, 'output_preview_banner' ) );
		
		// AJAX handler for switching themes
		add_action( 'wp_ajax_sts_switch_theme', array( $this, 'ajax_switch_theme' ) );
	}

	/**
	 * Enqueue scripts and styles for the preview banner.
	 *
	 * Only enqueues if:
	 * 1. User has permission to preview themes
	 * 2. Preview mode is enabled in settings
	 * 3. User is currently in preview mode with a valid theme
	 * 4. Preview banner is enabled in settings
	 *
	 * This ensures the banner only appears for authorized users in preview mode
	 * and never affects regular visitors.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		// Get theme switcher instance
		$theme_switcher = new ThemeSwitcher();
		
		// Get settings
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		
		// Check if preview mode is enabled in settings
		$preview_enabled = isset( $settings['enable_preview'] ) && $settings['enable_preview'] === 'yes';
		if ( ! $preview_enabled ) {
			return;
		}
		
		// Check if user has permission to preview
		if ( ! $theme_switcher->can_user_preview() ) {
			return;
		}
		
		// Check if user is currently in preview mode
		$preview_theme = $theme_switcher->get_preview_theme();
		if ( ! $preview_theme ) {
			return;
		}
		
		// Check if preview banner is enabled
		$enable_banner = isset( $settings['enable_preview_banner'] ) ? 'yes' === $settings['enable_preview_banner'] : true;
		if ( ! $enable_banner ) {
			return;
		}

		// Enqueue banner CSS
		wp_enqueue_style(
			'sts-preview-banner',
			STS_PLUGIN_URL . 'assets/dist/preview-banner.css',
			array(),
			STS_PLUGIN_VERSION
		);

		// Enqueue banner JS
		wp_enqueue_script(
			'sts-preview-banner',
			STS_PLUGIN_URL . 'assets/dist/preview-banner.js',
			array( 'jquery' ),
			STS_PLUGIN_VERSION,
			true
		);

		// Localize script
		wp_localize_script(
			'sts-preview-banner',
			'PreviewBanner',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'sts-preview-banner-nonce' ),
				'currentUrl'    => esc_url( remove_query_arg( $theme_switcher->get_query_param_name() ) ),
				'queryParam'    => $theme_switcher->get_query_param_name(),
				'currentTheme'  => $preview_theme,
			)
		);
	}

	/**
	 * AJAX handler for switching themes in Preview Mode.
	 *
	 * This only works for authorized users with preview permission.
	 * It never affects visitors or SEO.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function ajax_switch_theme() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'sts-preview-banner-nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid nonce. Please refresh the page and try again.', 'smart-theme-switcher' ) ) );
		}

		// Get theme switcher instance
		$theme_switcher = new ThemeSwitcher();
		
		// Check if preview mode is enabled in settings
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$preview_enabled = isset( $settings['enable_preview'] ) && $settings['enable_preview'] === 'yes';
		if ( ! $preview_enabled ) {
			wp_send_json_error( array( 'message' => __( 'Preview mode is disabled in settings.', 'smart-theme-switcher' ) ) );
		}
		
		// Check if user has permission to preview
		if ( ! $theme_switcher->can_user_preview() ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to preview themes.', 'smart-theme-switcher' ) ) );
		}

		// Get theme from request
		$theme = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '';
		
		// Check if theme exists
		if ( empty( $theme ) || ! wp_get_theme( $theme )->exists() ) {
			wp_send_json_error( array( 'message' => __( 'Invalid theme selection.', 'smart-theme-switcher' ) ) );
		}

		// Get current URL
		$current_url = isset( $_POST['currentUrl'] ) ? esc_url_raw( wp_unslash( $_POST['currentUrl'] ) ) : '';
		
		// Build new URL with theme parameter
		$new_url = add_query_arg( $theme_switcher->get_query_param_name(), $theme, $current_url );
		
		// Send success response
		wp_send_json_success( array(
			'message' => __( 'Theme switched successfully.', 'smart-theme-switcher' ),
			'url'     => $new_url,
		) );
	}

	/**
	 * Output the preview banner in the footer.
	 * 
	 * This banner is only shown to authorized users in preview mode,
	 * never to regular visitors.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function output_preview_banner() {
		// Get theme switcher instance
		$theme_switcher = new ThemeSwitcher();
		
		// Check if preview mode is enabled in settings
		$settings = get_option( 'smart_theme_switcher_settings', array() );
		$preview_enabled = isset( $settings['enable_preview'] ) && $settings['enable_preview'] === 'yes';
		if ( ! $preview_enabled ) {
			return;
		}
		
		// Check if user has permission to preview
		if ( ! $theme_switcher->can_user_preview() ) {
			return;
		}
		
		// Check if user is currently in preview mode
		$preview_theme = $theme_switcher->get_preview_theme();
		if ( ! $preview_theme ) {
			return;
		}
		
		// Get available themes
		$themes = $theme_switcher->get_available_themes();
		
		// Get assigned theme (what would be shown if not previewing)
		$assigned_theme = $theme_switcher->get_assigned_theme();
		$assigned_theme_name = $assigned_theme ? $themes[$assigned_theme] : __( 'Default Theme', 'smart-theme-switcher' );
		
		// Get current theme name
		$current_theme_name = isset( $themes[$preview_theme] ) ? $themes[$preview_theme] : $preview_theme;
		
		// Get query parameter
		$query_param = $theme_switcher->get_query_param_name();
		
		// Get current URL without the preview parameter
		$current_url = remove_query_arg( $query_param );
		
		// Output the banner
		?>
		<div id="sts-preview-banner" class="sts-preview-banner" role="complementary" aria-label="<?php esc_attr_e( 'Theme Preview Controls', 'smart-theme-switcher' ); ?>">
			<div class="sts-preview-banner-inner">
				<span class="sts-preview-label"><?php esc_html_e( 'Previewing:', 'smart-theme-switcher' ); ?></span>
				<strong class="sts-preview-theme-name"><?php echo esc_html( $current_theme_name ); ?></strong>
				
				<div class="sts-preview-controls">
					<label for="sts-theme-select" class="screen-reader-text"><?php esc_html_e( 'Select theme to preview', 'smart-theme-switcher' ); ?></label>
					<select id="sts-theme-select" class="sts-theme-select">
						<option value=""><?php esc_html_e( '— Switch Theme —', 'smart-theme-switcher' ); ?></option>
						<option value="<?php echo esc_attr( $assigned_theme ); ?>" <?php selected( $preview_theme, $assigned_theme ); ?>>
							<?php esc_html_e( 'Use Assigned Theme', 'smart-theme-switcher' ); ?> (<?php echo esc_html( $assigned_theme_name ); ?>)
						</option>
						<?php foreach ( $themes as $slug => $name ) : ?>
							<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $preview_theme, $slug ); ?>>
								<?php echo esc_html( $name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<a href="<?php echo esc_url( $current_url ); ?>" class="sts-exit-preview-button button">
						<?php esc_html_e( 'Exit Preview', 'smart-theme-switcher' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}
}