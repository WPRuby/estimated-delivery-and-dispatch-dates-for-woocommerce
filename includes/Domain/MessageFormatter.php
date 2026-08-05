<?php
/**
 * Message formatter.
 *
 * @package WPRuby\DeliveryPromise
 */

namespace WPRuby\DeliveryPromise\Domain;

use DateTimeImmutable;
use WPRuby\DeliveryPromise\Infrastructure\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class MessageFormatter
 *
 * Replaces template placeholders with formatted, timezone-aware dates.
 */
class MessageFormatter {

	/**
	 * Settings accessor.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings accessor.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Format a message template using values from an estimate.
	 *
	 * @param string   $template Message template.
	 * @param Estimate $estimate Calculated estimate.
	 * @param string   $context  Output context.
	 *
	 * @return string
	 */
	public function format( string $template, Estimate $estimate, string $context = '' ): string {
		$replacements = $this->placeholders( $estimate );
		$message      = strtr( $template, $replacements );

		/**
		 * Filter the formatted display message.
		 *
		 * @param string   $message      Formatted message.
		 * @param string   $template     Original template.
		 * @param Estimate $estimate     Estimate used.
		 * @param string   $context      Output context.
		 * @param array    $replacements Placeholder replacement map.
		 */
		return (string) apply_filters( 'eddd_message', $message, $template, $estimate, $context, $replacements );
	}

	/**
	 * Build the placeholder replacement map for an estimate.
	 *
	 * @param Estimate $estimate Estimate.
	 *
	 * @return array<string,string>
	 */
	public function placeholders( Estimate $estimate ): array {
		$earliest = $this->format_date( $estimate->delivery_min() );
		$latest   = $this->format_date( $estimate->delivery_max() );

		return array(
			'{earliest_date}'     => $earliest,
			'{latest_date}'       => $latest,
			'{processing_days}'   => $this->format_day_range( $estimate->processing_min(), $estimate->processing_max() ),
			'{min_transit_days}'  => (string) $estimate->transit_min(),
			'{max_transit_days}'  => (string) $estimate->transit_max(),
			'{dispatch_date}'     => $this->humanize( $estimate->dispatch_min() ),
			'{dispatch_range}'    => $this->format_range( $estimate->dispatch_min(), $estimate->dispatch_max() ),
			'{delivery_date}'     => $this->humanize( $estimate->delivery_min() ),
			'{delivery_range}'    => $this->format_range( $estimate->delivery_min(), $estimate->delivery_max() ),
			'{min_delivery_date}' => $earliest,
			'{max_delivery_date}' => $latest,
			'{cutoff_time}'       => $this->format_cutoff( $estimate->cutoff_time() ),
		);
	}

	/**
	 * Format a single date using the store timezone and configured format.
	 *
	 * @param DateTimeImmutable $date Date.
	 *
	 * @return string
	 */
	public function format_date( DateTimeImmutable $date ): string {
		if ( function_exists( 'wp_date' ) ) {
			return (string) wp_date( $this->settings->date_format(), $date->getTimestamp() );
		}

		return $date->format( $this->settings->date_format() );
	}

	/**
	 * Format a date range, collapsing to a single date when min equals max.
	 *
	 * @param DateTimeImmutable $min Range start.
	 * @param DateTimeImmutable $max Range end.
	 *
	 * @return string
	 */
	public function format_range( DateTimeImmutable $min, DateTimeImmutable $max ): string {
		if ( $min->format( 'Y-m-d' ) === $max->format( 'Y-m-d' ) ) {
			return $this->format_date( $min );
		}

		/**
		 * Filter the separator used between range dates.
		 *
		 * @param string $separator Separator string.
		 */
		$separator = (string) apply_filters( 'eddd_range_separator', ' – ' );

		return $this->format_date( $min ) . $separator . $this->format_date( $max );
	}

	/**
	 * Format a day count or range for placeholders.
	 *
	 * @param int $min Minimum days.
	 * @param int $max Maximum days.
	 *
	 * @return string
	 */
	public function format_day_range( int $min, int $max ): string {
		if ( $min === $max ) {
			return (string) $min;
		}

		return $min . '–' . $max;
	}

	/**
	 * Produce a human friendly single date (Today / Tomorrow / formatted date).
	 *
	 * @param DateTimeImmutable $date Date.
	 *
	 * @return string
	 */
	private function humanize( DateTimeImmutable $date ): string {
		$tz       = function_exists( 'wp_timezone' ) ? wp_timezone() : $date->getTimezone();
		$today    = ( new DateTimeImmutable( 'now', $tz ) )->format( 'Y-m-d' );
		$tomorrow = ( new DateTimeImmutable( 'now', $tz ) )->modify( '+1 day' )->format( 'Y-m-d' );
		$target   = $date->format( 'Y-m-d' );

		if ( $target === $today ) {
			return __( 'today', 'wpruby-delivery-estimates' );
		}

		if ( $target === $tomorrow ) {
			return __( 'tomorrow', 'wpruby-delivery-estimates' );
		}

		return $this->format_date( $date );
	}

	/**
	 * Format the cutoff time using the store time format.
	 *
	 * @param string $cutoff Cutoff time (HH:MM).
	 *
	 * @return string
	 */
	private function format_cutoff( string $cutoff ): string {
		if ( '' === $cutoff || ! preg_match( '/^(\d{1,2}):(\d{2})$/', $cutoff ) ) {
			return '';
		}

		$timestamp = strtotime( $cutoff );
		if ( false === $timestamp ) {
			return $cutoff;
		}

		$format = (string) get_option( 'time_format', 'g:i a' );

		if ( function_exists( 'wp_date' ) ) {
			return (string) wp_date( $format, $timestamp );
		}

		return gmdate( $format, $timestamp );
	}
}
