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
		$this->assertArrayHasKey( 'hrefRegExp', $value );
		$this->assertSame( 'regexp for test', $value[ 'hrefRegExp' ] );
	}

	public function testGetValue_hrefRegExpMissing() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray() );
		$value = $provider->getValue();
		$this->assertArrayHasKey( 'hrefRegExp', $value );
		$this->assertNull( $value[ 'hrefRegExp' ] );
	}

	public function testGetValue_editTagsProvided() {
		$tags = [ 'tag1', 'tag2', 'tag3' ];
		$settings = new SettingsArray( [
			'dataBridgeEditTags' => $tags,
		] );

		$provider = new DataBridgeConfigValueProvider( $settings );
		$value = $provider->getValue();
		$this->assertArrayHasKey( 'editTags', $value );
		$this->assertSame( $tags, $value[ 'editTags' ] );
	}

	public function testGetValue_editTagsMissing() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray() );
		$value = $provider->getValue();
		$this->assertSame( [], $value[ 'editTags' ] );
	}

}
