<?php
/**
 * PHPUnit bootstrap.
 *
 * Supports two modes:
 * - Unit mode: lightweight WordPress function shims for fast pure/domain tests.
 * - Integration mode: load the WordPress test suite when WPRUBY_DP_LOAD_WP_TESTS=1.
 *
 * @package WPRuby\DeliveryPromise\Tests
 */

if ( ! defined( 'WPRUBY_DP_TESTING' ) ) {
	define( 'WPRUBY_DP_TESTING', true );
}

$plugin_root = dirname( __DIR__ );

$composer = $plugin_root . '/vendor/autoload.php';
if ( is_readable( $composer ) ) {
	require_once $composer;
}

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: '/tmp/wordpress-tests-lib';
if ( getenv( 'WPRUBY_DP_LOAD_WP_TESTS' ) && is_readable( $wp_tests_dir . '/includes/functions.php' ) ) {
	require_once $wp_tests_dir . '/includes/functions.php';

	tests_add_filter(
		'muplugins_loaded',
		static function () use ( $plugin_root ) {
			$woocommerce = getenv( 'WP_TESTS_WC_PLUGIN' );
			if ( $woocommerce && is_readable( $woocommerce ) ) {
				require_once $woocommerce;
			}

			require_once $plugin_root . '/estimated-delivery-and-dispatch-dates-for-woocommerce.php';
		}
	);

	require_once $wp_tests_dir . '/includes/bootstrap.php';
	return;
}

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', $plugin_root . '/tests/wordpress/' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}

if ( ! defined( 'EDDD_VERSION' ) ) {
	define( 'EDDD_VERSION', '1.0.0' );
	define( 'EDDD_PLUGIN_FILE', $plugin_root . '/estimated-delivery-and-dispatch-dates-for-woocommerce.php' );
	define( 'EDDD_PLUGIN_DIR', $plugin_root . '/' );
	define( 'EDDD_PLUGIN_URL', 'https://example.test/wp-content/plugins/estimated-delivery-and-dispatch-dates-for-woocommerce/' );
	define( 'EDDD_TEXT_DOMAIN', 'wpruby-delivery-estimates' );
	define( 'EDDD_BASENAME', 'estimated-delivery-and-dispatch-dates-for-woocommerce/estimated-delivery-and-dispatch-dates-for-woocommerce.php' );
}

$GLOBALS['wpruby_dp_test_options'] = array(
	'date_format' => 'F j, Y',
	'time_format' => 'g:i a',
	'timezone_string' => 'UTC',
);

if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$args = get_object_vars( $args );
		} elseif ( ! is_array( $args ) ) {
			parse_str( (string) $args, $args );
		}

		return array_merge( $defaults, $args );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $hook_name, $value ) {
		unset( $hook_name );
		return $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	function do_action( $hook_name, ...$args ) {
		unset( $hook_name, $args );
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook_name, $callback = null, $priority = 10, $accepted_args = 1 ) {
		unset( $hook_name, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $hook_name, $callback = null, $priority = 10, $accepted_args = 1 ) {
		unset( $hook_name, $callback, $priority, $accepted_args );
		return true;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		unset( $domain );
		return $text;
	}
}

if ( ! function_exists( '_n' ) ) {
	function _n( $single, $plural, $number, $domain = 'default' ) {
		unset( $domain );
		return 1 === (int) $number ? $single : $plural;
	}
}

if ( ! function_exists( '_x' ) ) {
	function _x( $text, $context, $domain = 'default' ) {
		unset( $context, $domain );
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( __( $text, $domain ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return trim( preg_replace( '/[\r\n\t ]+/', ' ', wp_strip_all_tags( (string) $value ) ) );
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	function sanitize_email( $email ) {
		return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-zA-Z0-9_\-]/', '', (string) $key ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $text ) {
		return strip_tags( (string) $text );
	}
}

if ( ! function_exists( 'absint' ) ) {
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return array_key_exists( $option, $GLOBALS['wpruby_dp_test_options'] ) ? $GLOBALS['wpruby_dp_test_options'][ $option ] : $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		unset( $autoload );
		$GLOBALS['wpruby_dp_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( $option, $value = '', $deprecated = '', $autoload = 'yes' ) {
		unset( $deprecated, $autoload );
		if ( array_key_exists( $option, $GLOBALS['wpruby_dp_test_options'] ) ) {
			return false;
		}
		$GLOBALS['wpruby_dp_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		unset( $GLOBALS['wpruby_dp_test_options'][ $option ] );
		return true;
	}
}

if ( ! function_exists( 'wp_timezone' ) ) {
	function wp_timezone() {
		$timezone = (string) get_option( 'timezone_string', 'UTC' );
		return new DateTimeZone( '' !== $timezone ? $timezone : 'UTC' );
	}
}

if ( ! function_exists( 'wp_date' ) ) {
	function wp_date( $format, $timestamp = null, $timezone = null ) {
		$timezone = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
		$date     = new DateTimeImmutable( '@' . ( null === $timestamp ? time() : (int) $timestamp ) );
		return $date->setTimezone( $timezone )->format( (string) $format );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		$timezone = $gmt ? new DateTimeZone( 'UTC' ) : wp_timezone();
		$now      = new DateTimeImmutable( 'now', $timezone );
		return 'timestamp' === $type ? $now->getTimestamp() : $now->format( 'Y-m-d H:i:s' );
	}
}

if ( ! function_exists( 'get_date_from_gmt' ) ) {
	function get_date_from_gmt( $string, $format = 'Y-m-d H:i:s' ) {
		$date = new DateTimeImmutable( (string) $string, new DateTimeZone( 'UTC' ) );
		return $date->setTimezone( wp_timezone() )->format( (string) $format );
	}
}

if ( ! function_exists( 'home_url' ) ) {
	function home_url( $path = '' ) {
		return 'https://example.test' . $path;
	}
}

if ( ! function_exists( 'untrailingslashit' ) ) {
	function untrailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( (string) $url, $component );
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		private $code;
		private $message;
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}

		public function add_data( $data ) {
			$this->data = $data;
		}
	}
}

if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return (string) $data;
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	function sanitize_title( $title ) {
		return strtolower( preg_replace( '/[^a-z0-9]+/i', '-', (string) $title ) );
	}
}

require_once $plugin_root . '/includes/autoload.php';
