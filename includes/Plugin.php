<?php
/**
 * Main plugin container and bootstrapper.
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise;

use WPRuby\DeliveryPromise\Admin\AdminApp;
use WPRuby\DeliveryPromise\Domain\Calendar;
use WPRuby\DeliveryPromise\Domain\LiteDeliveryCalculator;
use WPRuby\DeliveryPromise\Domain\MessageFormatter;
use WPRuby\DeliveryPromise\Infrastructure\Assets;
use WPRuby\DeliveryPromise\Infrastructure\Settings;
use WPRuby\DeliveryPromise\Rest\RestController;
use WPRuby\DeliveryPromise\WooCommerce\ProductPage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Plugin
 *
 * Acts as a lightweight service container and wires the Lite modules together.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Calendar service.
	 *
	 * @var Calendar
	 */
	private $calendar;

	/**
	 * Delivery calculator service.
	 *
	 * @var LiteDeliveryCalculator
	 */
	private $calculator;

	/**
	 * Message formatter service.
	 *
	 * @var MessageFormatter
	 */
	private $message_formatter;

	/**
	 * Get the singleton instance.
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor: wire everything up.
	 */
	private function __construct() {
		load_plugin_textdomain(
			EDDD_TEXT_DOMAIN,
			false,
			dirname( EDDD_BASENAME ) . '/languages'
		);

		if ( ! $this->is_woocommerce_active() ) {
			return;
		}

		$this->boot();
	}

	/**
	 * Instantiate services and hook integrations.
	 *
	 * @return void
	 */
	private function boot(): void {
		$this->settings          = new Settings();
		$this->calendar          = new Calendar( $this->settings );
		$this->message_formatter = new MessageFormatter( $this->settings );
		$this->calculator        = new LiteDeliveryCalculator( $this->settings, $this->calendar );

		( new Assets( $this->settings ) )->register();

		( new RestController(
			$this->settings,
			$this->calculator,
			$this->message_formatter
		) )->register();

		if ( is_admin() ) {
			( new AdminApp() )->register();
		}

		( new ProductPage( $this->settings, $this->calculator, $this->message_formatter ) )->register();
	}

	/**
	 * Whether WooCommerce is active.
	 *
	 * @return bool
	 */
	public function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Settings accessor.
	 *
	 * @return Settings
	 */
	public function settings(): Settings {
		return $this->settings;
	}

	/**
	 * Calculator accessor.
	 *
	 * @return LiteDeliveryCalculator
	 */
	public function calculator(): LiteDeliveryCalculator {
		return $this->calculator;
	}

	/**
	 * Message formatter accessor.
	 *
	 * @return MessageFormatter
	 */
	public function message_formatter(): MessageFormatter {
		return $this->message_formatter;
	}
}
