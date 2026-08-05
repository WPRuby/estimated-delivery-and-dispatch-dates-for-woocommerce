<?php
/**
 * Product page display.
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise\WooCommerce;

use WC_Product;
use WPRuby\DeliveryPromise\Domain\LiteDeliveryCalculator;
use WPRuby\DeliveryPromise\Domain\MessageFormatter;
use WPRuby\DeliveryPromise\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class ProductPage
 *
 * Shows a simple delivery estimate on single product pages.
 */
class ProductPage {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Calculator.
	 *
	 * @var LiteDeliveryCalculator
	 */
	private $calculator;

	/**
	 * Message formatter.
	 *
	 * @var MessageFormatter
	 */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @param Settings               $settings   Settings accessor.
	 * @param LiteDeliveryCalculator $calculator Calculator.
	 * @param MessageFormatter       $formatter  Message formatter.
	 */
	public function __construct( Settings $settings, LiteDeliveryCalculator $calculator, MessageFormatter $formatter ) {
		$this->settings   = $settings;
		$this->calculator = $calculator;
		$this->formatter  = $formatter;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register(): void {
		if ( ! $this->settings->is_enabled() || ! $this->settings->is_display_enabled( 'product' ) ) {
			return;
		}

		$placement  = $this->settings->product_placement();
		$priorities = $this->settings->placement_priorities();
		$priority   = $priorities[ $placement ] ?? 35;

		add_action( 'woocommerce_single_product_summary', array( $this, 'render' ), $priority );
		add_filter( 'woocommerce_available_variation', array( $this, 'available_variation' ), 20, 3 );
	}

	/**
	 * Render the estimate.
	 *
	 * @return void
	 */
	public function render(): void {
		$product = wc_get_product( get_the_ID() );

		if ( ! $this->should_show_for_product( $product ) ) {
			return;
		}

		echo wp_kses_post( $this->render_html( $product ) );
	}

	/**
	 * Add estimate data to WooCommerce variation payloads.
	 *
	 * @param array<string,mixed> $data      Variation data.
	 * @param WC_Product          $product   Parent product.
	 * @param WC_Product          $variation Variation product.
	 *
	 * @return array<string,mixed>
	 */
	public function available_variation( array $data, WC_Product $product, WC_Product $variation ): array {
		unset( $product );

		if ( ! $this->should_show_for_product( $variation ) ) {
			$data['eddd'] = array(
				'hidden'  => true,
				'message' => '',
			);

			return $data;
		}

		$message = $this->build_message();

		$data['eddd'] = array(
			'hidden'  => '' === trim( wp_strip_all_tags( $message ) ),
			'message' => wp_kses_post( $message ),
		);

		return $data;
	}

	/**
	 * Whether the estimate should show for a product.
	 *
	 * @param WC_Product|null $product Product.
	 *
	 * @return bool
	 */
	private function should_show_for_product( $product ): bool {
		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return false;
		}

		if ( $this->settings->in_stock_only() && ! $product->is_in_stock() ) {
			return false;
		}

		return true;
	}

	/**
	 * Build the formatted message for the current settings.
	 *
	 * @return string
	 */
	private function build_message(): string {
		$estimate = $this->calculator->calculate();
		$template = (string) $this->settings->get( 'message_product', '' );

		/**
		 * Filter the product-page delivery promise message template.
		 *
		 * @param string    $template Message template.
		 * @param WC_Product|null $product Product (may be null in preview contexts).
		 */
		$template = (string) apply_filters( 'eddd_product_message_template', $template, null );

		return $this->formatter->format( $template, $estimate, 'product' );
	}

	/**
	 * Render the estimate HTML for a product.
	 *
	 * @param WC_Product $product Product.
	 *
	 * @return string
	 */
	private function render_html( WC_Product $product ): string {
		$message = $this->build_message();

		if ( '' === trim( wp_strip_all_tags( $message ) ) ) {
			return '';
		}

		$style    = $this->settings->display_style();
		$show_icon = $this->settings->show_icon();

		ob_start();
		include EDDD_PLUGIN_DIR . 'templates/frontend/lite-estimate.php';

		$html = (string) ob_get_clean();

		/**
		 * Filter the rendered product-page delivery promise HTML.
		 *
		 * @param string     $html    Rendered HTML.
		 * @param WC_Product $product Product.
		 * @param string     $message Formatted message.
		 */
		return (string) apply_filters( 'eddd_product_html', $html, $product, $message );
	}
}
