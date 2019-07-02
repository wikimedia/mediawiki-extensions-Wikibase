<?php

namespace Wikibase\Client\Tests\DataBridge;

use PHPUnit\Framework\TestCase;
use Wikibase\Client\DataBridge\DataBridgeConfigValueProvider;
use Wikibase\SettingsArray;

/**
 * @covers \Wikibase\Client\DataBridge\DataBridgeConfigValueProvider
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class DataBridgeConfigValueProviderTest extends TestCase {

	public function testGetKey() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray() );
		$key = $provider->getKey();
		$this->assertSame( 'wbDataBridgeConfig', $key );
	}

	public function testGetValue_hrefRegExpProvided() {
		$settings = new SettingsArray( [
			'dataBridgeHrefRegExp' => 'regexp for test',
		] );
		$provider = new DataBridgeConfigValueProvider( $settings );
		$value = $provider->getValue();
		$this->assertSame(
			[
				'hrefRegExp' => 'regexp for test',
			],
			$value
		);
	}

	public function testGetValue_hrefRegExpMissing() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray() );
		$value = $provider->getValue();
		$this->assertSame(
			[
				'hrefRegExp' => null,
			],
			$value
		);
	}

}
