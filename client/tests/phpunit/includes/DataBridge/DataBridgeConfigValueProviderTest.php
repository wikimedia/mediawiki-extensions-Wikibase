<?php

namespace Wikibase\Client\Tests\DataBridge;

use PHPUnit\Framework\TestCase;
use Wikibase\Client\DataBridge\DataBridgeConfigValueProvider;

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
		$provider = new DataBridgeConfigValueProvider();
		$key = $provider->getKey();
		$this->assertSame( 'wbDataBridgeConfig', $key );
	}

	public function testGetValue() {
		$provider = new DataBridgeConfigValueProvider();
		$value = $provider->getValue();
		$this->assertSame(
			[
				'hrefRegExp' => 'https://www\.wikidata\.org/wiki/(Q[1-9][0-9]*).*#(P[1-9][0-9]*)',
			],
			$value
		);
	}

}
