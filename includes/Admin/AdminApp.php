<?php
/**
 * Admin app page (Vue mount point).
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AdminApp
 *
 * Registers the WooCommerce admin page and renders the Vue mount point.
 */
class AdminApp {

	const PAGE_SLUG  = 'wpruby-delivery-estimates';
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_filter( 'plugin_action_links_' . EDDD_BASENAME, array( $this, 'action_links' ) );
	}

	/**
	 * Add the submenu page under WooCommerce.
	 *
	 * @return void
	 */
	public function add_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'WPRuby Delivery Estimates for WooCommerce', 'wpruby-delivery-estimates' ),
			__( 'Estimated Delivery', 'wpruby-delivery-estimates' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a settings link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 *
	 * @return array
	 */
	public function action_links( array $links ): array {
		$url  = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'wpruby-delivery-estimates' ) . '</a>';
		array_unshift( $links, $link );

		return $links;
	}

	/**
	 * Render the Vue mount point.
	 *
	 * @return void
	 */
	public function render_page(): void {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		echo '<div id="eddd-admin" class="eddd-admin">';
		echo '<p class="eddd-admin__loading">' . esc_html__( 'Loading Estimated Delivery…', 'wpruby-delivery-estimates' ) . '</p>';
		echo '</div>';
	}
}
