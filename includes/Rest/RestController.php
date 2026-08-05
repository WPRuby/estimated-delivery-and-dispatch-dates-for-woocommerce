<?php
/**
 * REST API controller for the Vue admin app.
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise\Rest;

use DateTimeImmutable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WPRuby\DeliveryPromise\Admin\AdminData;
use WPRuby\DeliveryPromise\Domain\LiteDeliveryCalculator;
use WPRuby\DeliveryPromise\Domain\MessageFormatter;
use WPRuby\DeliveryPromise\Infrastructure\Sanitizer;
use WPRuby\DeliveryPromise\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RestController
 *
 * Registers and handles Lite REST routes for the admin Vue app.
 */
class RestController {

	const NAMESPACE  = 'estimated-delivery-and-dispatch-dates-for-woocommerce/v1';
	const CAPABILITY = 'manage_woocommerce';

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Delivery calculator.
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
	 * @param LiteDeliveryCalculator $calculator Delivery calculator.
	 * @param MessageFormatter       $formatter  Message formatter.
	 */
	public function __construct(
		Settings $settings,
		LiteDeliveryCalculator $calculator,
		MessageFormatter $formatter
	) {
		$this->settings   = $settings;
		$this->calculator = $calculator;
		$this->formatter  = $formatter;
	}

	/**
	 * Register the REST routes.
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register all routes under the plugin namespace.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$auth = array( $this, 'check_permission' );

		register_rest_route(
			self::NAMESPACE,
			'/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => $auth,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => $auth,
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/data',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_data' ),
				'permission_callback' => $auth,
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/preview',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'preview' ),
				'permission_callback' => $auth,
			)
		);
	}

	/**
	 * Permission callback.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return new WP_Error(
				'eddd_forbidden',
				__( 'You do not have permission to manage Estimated Delivery settings.', 'wpruby-delivery-estimates' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * GET /settings
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings(): WP_REST_Response {
		return new WP_REST_Response( $this->settings_for_app(), 200 );
	}

	/**
	 * POST /settings
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function save_settings( WP_REST_Request $request ) {
		$raw = (array) $request->get_json_params();
		if ( empty( $raw ) ) {
			$raw = (array) $request->get_params();
		}

		$clean = array(
			'enabled'           => Sanitizer::checkbox( $this->bool_in( $raw, 'enabled' ) ),
			'display_product'   => Sanitizer::checkbox( $this->bool_in( $raw, 'display_product' ) ),
			'in_stock_only'     => Sanitizer::checkbox( $this->bool_in( $raw, 'in_stock_only' ) ),
			'processing_min'    => Sanitizer::days( $raw['processing_min'] ?? 1 ),
			'processing_max'    => Sanitizer::days( $raw['processing_max'] ?? 1 ),
			'transit_min'       => Sanitizer::days( $raw['transit_min'] ?? 2 ),
			'transit_max'       => Sanitizer::days( $raw['transit_max'] ?? 4 ),
			'cutoff_time'       => Sanitizer::time( $raw['cutoff_time'] ?? '' ),
			'working_days'      => Sanitizer::working_days( $raw['working_days'] ?? array() ),
			'holidays'          => Sanitizer::holidays( $raw['holidays'] ?? array() ),
			'message_product'   => Sanitizer::message( $raw['message_product'] ?? '' ),
			'product_placement' => Sanitizer::product_placement( $raw['product_placement'] ?? '' ),
			'display_style'     => Sanitizer::display_style( $raw['display_style'] ?? '' ),
			'show_icon'         => Sanitizer::checkbox( $this->bool_in( $raw, 'show_icon' ) ),
		);

		$clean['processing_max'] = max( $clean['processing_min'], $clean['processing_max'] );
		$clean['transit_max']    = max( $clean['transit_min'], $clean['transit_max'] );

		if ( empty( $clean['working_days'] ) ) {
			$clean['working_days'] = array( 1, 2, 3, 4, 5 );
		}

		$this->settings->save( $clean );

		return new WP_REST_Response(
			array(
				'saved'    => true,
				'settings' => $this->settings_for_app(),
			),
			200
		);
	}

	/**
	 * GET /data
	 *
	 * @return WP_REST_Response
	 */
	public function get_data(): WP_REST_Response {
		return new WP_REST_Response( AdminData::all(), 200 );
	}

	/**
	 * POST /preview
	 *
	 * @param WP_REST_Request $request Request.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function preview( WP_REST_Request $request ) {
		$params = (array) $request->get_json_params();

		$template = isset( $params['message_product'] )
			? Sanitizer::message( $params['message_product'] )
			: (string) $this->settings->get( 'message_product', '' );

		$now = null;
		if ( ! empty( $params['current_datetime'] ) ) {
			$parsed = $this->sanitize_preview_datetime( (string) $params['current_datetime'] );
			if ( is_wp_error( $parsed ) ) {
				return $parsed;
			}
			$now = $parsed;
		}

		$overrides = array(
			'processing_min' => isset( $params['processing_min'] ) ? Sanitizer::days( $params['processing_min'] ) : null,
			'processing_max' => isset( $params['processing_max'] ) ? Sanitizer::days( $params['processing_max'] ) : null,
			'transit_min'    => isset( $params['transit_min'] ) ? Sanitizer::days( $params['transit_min'] ) : null,
			'transit_max'    => isset( $params['transit_max'] ) ? Sanitizer::days( $params['transit_max'] ) : null,
			'cutoff_time'    => isset( $params['cutoff_time'] ) ? Sanitizer::time( $params['cutoff_time'] ) : null,
			'working_days'   => isset( $params['working_days'] ) ? Sanitizer::working_days( $params['working_days'] ) : null,
			'holidays'       => isset( $params['holidays'] ) ? Sanitizer::holidays( $params['holidays'] ) : null,
		);
		$overrides = array_filter(
			$overrides,
			static function ( $value ) {
				return null !== $value;
			}
		);

		$estimate     = $this->calculator->calculate( $now, $overrides );
		$placeholders = $this->formatter->placeholders( $estimate );
		$message      = $this->formatter->format( $template, $estimate, 'preview' );

		return new WP_REST_Response(
			array(
				'message'       => $message,
				'placeholders'  => $placeholders,
				'deliveryRange' => $placeholders['{delivery_range}'],
				'earliestDate'  => $placeholders['{earliest_date}'],
				'latestDate'    => $placeholders['{latest_date}'],
			),
			200
		);
	}

	/**
	 * Build the settings payload for the app.
	 *
	 * @return array<string,mixed>
	 */
	private function settings_for_app(): array {
		$s = $this->settings;

		return array(
			'enabled'           => $s->is_enabled(),
			'display_product'     => $s->is_display_enabled( 'product' ),
			'in_stock_only'       => $s->in_stock_only(),
			'processing_min'      => (int) $s->get( 'processing_min', 1 ),
			'processing_max'      => (int) $s->get( 'processing_max', 1 ),
			'transit_min'         => (int) $s->get( 'transit_min', 2 ),
			'transit_max'         => (int) $s->get( 'transit_max', 4 ),
			'cutoff_time'         => $s->cutoff_time(),
			'working_days'        => array_map( 'intval', $s->working_days() ),
			'holidays'            => $s->holidays_structured(),
			'message_product'     => (string) $s->get( 'message_product', '' ),
			'product_placement'   => $s->product_placement(),
			'display_style'       => $s->display_style(),
			'show_icon'           => $s->show_icon(),
		);
	}

	/**
	 * Read a boolean value from request data.
	 *
	 * @param array<string,mixed> $raw Raw request data.
	 * @param string              $key Setting key.
	 *
	 * @return mixed
	 */
	private function bool_in( array $raw, string $key ) {
		if ( ! array_key_exists( $key, $raw ) ) {
			return 'no';
		}

		return $raw[ $key ];
	}

	/**
	 * Parse an optional preview datetime in store timezone.
	 *
	 * @param string $value Raw datetime string.
	 *
	 * @return DateTimeImmutable|WP_Error
	 */
	private function sanitize_preview_datetime( string $value ) {
		$value = trim( $value );
		if ( '' === $value ) {
			return new WP_Error(
				'eddd_invalid_datetime',
				__( 'Preview datetime cannot be empty.', 'wpruby-delivery-estimates' ),
				array( 'status' => 400 )
			);
		}

		$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
		$dt = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, $tz );

		if ( ! $dt ) {
			$dt = DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $value, $tz );
		}

		if ( ! $dt ) {
			return new WP_Error(
				'eddd_invalid_datetime',
				__( 'Preview datetime must be a valid date/time.', 'wpruby-delivery-estimates' ),
				array( 'status' => 400 )
			);
		}

		return $dt;
	}
}
