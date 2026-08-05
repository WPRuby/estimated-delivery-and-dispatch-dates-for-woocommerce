<?php
/**
 * Settings accessor.
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise\Infrastructure;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 *
 * Reads and writes Lite plugin settings stored in a single option.
 * Working days are stored as ISO-8601 day numbers (1 = Monday ... 7 = Sunday).
 */
class Settings {

	const OPTION_KEY = 'eddd_settings';

	const PLACEMENT_AFTER_PRICE        = 'after_price';
	const PLACEMENT_BEFORE_ADD_TO_CART = 'before_add_to_cart';
	const PLACEMENT_AFTER_ADD_TO_CART  = 'after_add_to_cart';

	const STYLE_PLAIN       = 'plain';
	const STYLE_HIGHLIGHTED = 'highlighted';

	/**
	 * Cached settings array.
	 *
	 * @var array<string,mixed>|null
	 */
	private $cache = null;

	/**
	 * Return the default settings (filterable).
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		$defaults = array(
			'enabled'           => 'yes',
			'display_product'   => 'yes',
			'in_stock_only'     => 'yes',
			'processing_min'    => 1,
			'processing_max'    => 1,
			'transit_min'       => 2,
			'transit_max'       => 4,
			'cutoff_time'       => '14:00',
			'working_days'      => array( 1, 2, 3, 4, 5 ),
			'holidays'          => array(),
			'message_product'   => __( 'Order today and get it between {earliest_date} – {latest_date}.', 'wpruby-delivery-estimates' ),
			'product_placement' => self::PLACEMENT_AFTER_ADD_TO_CART,
			'display_style'     => self::STYLE_HIGHLIGHTED,
			'show_icon'         => 'yes',
		);

		/**
		 * Filter the default Lite plugin settings.
		 *
		 * @param array<string,mixed> $defaults Default settings.
		 */
		return (array) apply_filters( 'eddd_default_settings', $defaults );
	}

	/**
	 * Return the full settings array (stored values merged over defaults).
	 *
	 * @return array<string,mixed>
	 */
	public function all(): array {
		if ( null === $this->cache ) {
			$stored      = get_option( self::OPTION_KEY, array() );
			$stored      = is_array( $stored ) ? $stored : array();
			$this->cache = wp_parse_args( $stored, $this->defaults() );
		}

		return $this->cache;
	}

	/**
	 * Get a single setting value.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Default value if not set.
	 *
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		$all = $this->all();

		return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
	}

	/**
	 * Persist a settings array (already sanitized by the caller).
	 *
	 * @param array<string,mixed> $values Sanitized settings.
	 *
	 * @return void
	 */
	public function save( array $values ): void {
		$merged = wp_parse_args( $values, $this->defaults() );
		update_option( self::OPTION_KEY, $merged );
		$this->cache = null;
	}

	/**
	 * Whether the plugin output is enabled globally.
	 *
	 * @return bool
	 */
	public function is_enabled(): bool {
		return 'yes' === $this->get( 'enabled', 'yes' );
	}

	/**
	 * Whether the product page estimate is enabled.
	 *
	 * @return bool
	 */
	public function is_display_enabled( string $location = 'product' ): bool {
		unset( $location );

		return 'yes' === $this->get( 'display_product', 'yes' );
	}

	/**
	 * Whether estimates should only show for in-stock products.
	 *
	 * @return bool
	 */
	public function in_stock_only(): bool {
		return 'yes' === $this->get( 'in_stock_only', 'yes' );
	}

	/**
	 * Whether to show the delivery icon on the product page.
	 *
	 * @return bool
	 */
	public function show_icon(): bool {
		return 'yes' === $this->get( 'show_icon', 'yes' );
	}

	/**
	 * Product page placement hook priority map.
	 *
	 * @return array<string,int>
	 */
	public function placement_priorities(): array {
		return array(
			self::PLACEMENT_AFTER_PRICE        => 11,
			self::PLACEMENT_BEFORE_ADD_TO_CART => 29,
			self::PLACEMENT_AFTER_ADD_TO_CART  => 35,
		);
	}

	/**
	 * Resolved product page placement.
	 *
	 * @return string
	 */
	public function product_placement(): string {
		$placement = (string) $this->get( 'product_placement', self::PLACEMENT_AFTER_ADD_TO_CART );
		$allowed   = array_keys( $this->placement_priorities() );

		return in_array( $placement, $allowed, true ) ? $placement : self::PLACEMENT_AFTER_ADD_TO_CART;
	}

	/**
	 * Resolved display style.
	 *
	 * @return string
	 */
	public function display_style(): string {
		$style   = (string) $this->get( 'display_style', self::STYLE_HIGHLIGHTED );
		$allowed = array( self::STYLE_PLAIN, self::STYLE_HIGHLIGHTED );

		return in_array( $style, $allowed, true ) ? $style : self::STYLE_HIGHLIGHTED;
	}

	/**
	 * Working days as ISO-8601 day numbers (1 = Monday ... 7 = Sunday).
	 *
	 * @return int[]
	 */
	public function working_days(): array {
		$days = $this->get( 'working_days', array( 1, 2, 3, 4, 5 ) );
		$days = is_array( $days ) ? array_map( 'intval', $days ) : array( 1, 2, 3, 4, 5 );

		return empty( $days ) ? array( 1, 2, 3, 4, 5 ) : $days;
	}

	/**
	 * Holiday dates as Y-m-d strings.
	 *
	 * @return string[]
	 */
	public function holidays(): array {
		$holidays = $this->get( 'holidays', array() );

		if ( ! is_array( $holidays ) ) {
			return array();
		}

		$out = array();
		foreach ( $holidays as $holiday ) {
			if ( is_array( $holiday ) ) {
				$date = isset( $holiday['date'] ) ? (string) $holiday['date'] : '';
			} else {
				$date = (string) $holiday;
			}

			if ( '' !== $date ) {
				$out[] = $date;
			}
		}

		return array_values( $out );
	}

	/**
	 * Holiday entries as structured { date, label } arrays.
	 *
	 * @return array<int,array<string,string>>
	 */
	public function holidays_structured(): array {
		$holidays = $this->get( 'holidays', array() );

		if ( ! is_array( $holidays ) ) {
			return array();
		}

		$out = array();
		foreach ( $holidays as $holiday ) {
			if ( is_array( $holiday ) ) {
				$date  = isset( $holiday['date'] ) ? (string) $holiday['date'] : '';
				$label = isset( $holiday['label'] ) ? (string) $holiday['label'] : '';
			} else {
				$date  = (string) $holiday;
				$label = '';
			}

			if ( '' !== $date ) {
				$out[] = array(
					'date'  => $date,
					'label' => $label,
				);
			}
		}

		return $out;
	}

	/**
	 * Cutoff time in HH:MM (24h).
	 *
	 * @return string
	 */
	public function cutoff_time(): string {
		$value = (string) $this->get( 'cutoff_time', '14:00' );

		return '' !== $value ? $value : '14:00';
	}

	/**
	 * Resolve the date format to use for output.
	 *
	 * @return string
	 */
	public function date_format(): string {
		return (string) get_option( 'date_format', 'F j, Y' );
	}
}
