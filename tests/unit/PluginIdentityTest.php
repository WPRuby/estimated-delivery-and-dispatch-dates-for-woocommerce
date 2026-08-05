<?php
/**
 * Plugin identity tests.
 *
 * @package WPRuby\DeliveryPromise\Tests\Unit
 */

namespace WPRuby\DeliveryPromise\Tests\Unit;

use WPRuby\DeliveryPromise\Infrastructure\Settings;
use WPRuby\DeliveryPromise\Tests\TestCase;

class PluginIdentityTest extends TestCase {

	public function test_lite_constants_are_defined(): void {
		$this->assertSame( '1.0.0', EDDD_VERSION );
		$this->assertSame( 'wpruby-delivery-estimates', EDDD_TEXT_DOMAIN );
		$this->assertStringContainsString( 'estimated-delivery-and-dispatch-dates-for-woocommerce.php', EDDD_PLUGIN_FILE );
	}

	public function test_lite_settings_use_separate_option_key(): void {
		$this->assertSame( 'eddd_settings', Settings::OPTION_KEY );
	}
}
