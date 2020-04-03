<?php

namespace Wikibase\Client\Tests\Unit\DataBridge;

use PHPUnit\Framework\TestCase;
use Wikibase\Client\DataBridge\DataBridgeConfigValueProvider;
use Wikibase\Lib\SettingsArray;

/**
 * @covers \Wikibase\Client\DataBridge\DataBridgeConfigValueProvider
 *
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0-or-later
 */
class DataBridgeConfigValueProviderTest extends TestCase {

	/** @var array settings that are not optional */
	private const BASE_SETTINGS = [ 'dataBridgeIssueReportingLink' => '' ];

	public function testGetKey() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), false );
		$key = $provider->getKey();
		$this->assertSame( 'wbDataBridgeConfig', $key );
	}

	public function testNoExtraKeys() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), false );
		$actualKeys = array_keys( $provider->getValue() );
		$expectedKeys = [
			'hrefRegExp',
			'editTags',
			'usePublish',
			'issueReportingLink',
		];

		$this->assertEqualsCanonicalizing( $expectedKeys, $actualKeys );
	}

	public function testGetValue_hrefRegExpProvided() {
		$settings = new SettingsArray( [
			'dataBridgeHrefRegExp' => 'regexp for test',
		] + self::BASE_SETTINGS );
		$provider = new DataBridgeConfigValueProvider( $settings, false );
		$value = $provider->getValue();
		$this->assertSame( 'regexp for test', $value[ 'hrefRegExp' ] );
	}

	public function testGetValue_hrefRegExpMissing() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), false );
		$value = $provider->getValue();
		$this->assertNull( $value[ 'hrefRegExp' ] );
	}

	public function testGetValue_editTagsProvided() {
		$tags = [ 'tag1', 'tag2', 'tag3' ];
		$settings = new SettingsArray( [
			'dataBridgeEditTags' => $tags,
		] + self::BASE_SETTINGS );

		$provider = new DataBridgeConfigValueProvider( $settings, false );
		$value = $provider->getValue();
		$this->assertSame( $tags, $value[ 'editTags' ] );
	}

	public function testGetValue_editTagsMissing() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), false );
		$value = $provider->getValue();
		$this->assertSame( [], $value[ 'editTags' ] );
	}

	public function testGetValue_usePublishFalse() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), false );
		$value = $provider->getValue();
		$this->assertFalse( $value[ 'usePublish' ] );
	}

	public function testGetValue_usePublishTrue() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( self::BASE_SETTINGS ), true );
		$value = $provider->getValue();
		$this->assertTrue( $value[ 'usePublish' ] );
	}

	public function testGetValue_issueReportingLink() {
		$provider = new DataBridgeConfigValueProvider( new SettingsArray( [
			'dataBridgeIssueReportingLink' => 'https://custom.example',
		] + self::BASE_SETTINGS ), false );
		$value = $provider->getValue();
		$this->assertSame( 'https://custom.example', $value['issueReportingLink'] );
	}

}
