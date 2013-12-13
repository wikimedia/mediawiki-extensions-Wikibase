<?php

namespace Wikibase\Test;

use Language;
use MediaWikiSite;
use SiteStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\Settings;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\WikibaseClient
 *
 * @since 0.4
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class WikibaseClientTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return WikibaseClient
	 */
	private function getDefaultInstance() {
		return WikibaseClient::getDefaultInstance();
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getDefaultInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getDefaultInstance()->getStore();
		$this->assertInstanceOf( 'Wikibase\ClientStore', $returnValue );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdParser', $returnValue );
	}

	public function testEntityIdLabelFormatterReturnType() {
		$returnValue = $this->getDefaultInstance()->newEntityIdLabelFormatter( 'en' );
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdLabelFormatter', $returnValue );
	}

	public function testGetSnakFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactory() {
		$returnValue = $this->getDefaultInstance()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	/**
	 * @dataProvider getLangLinkSiteGroupProvider
	 */
	public function testGetLangLinkSiteGroup( $expected, $settings, $siteStore ) {
		$client = new WikibaseClient( $settings, Language::factory( 'en' ), true, $siteStore );
		$this->assertEquals( $expected, $client->getLangLinkSiteGroup() );
	}

	public function getLangLinkSiteGroupProvider() {
		$siteStore = $this->getMockSiteStore();

		$settings = clone Settings::singleton();
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', null );

		$settings2 = clone Settings::singleton();
		$settings2->setSetting( 'siteGlobalID', 'enwiki' );
		$settings2->setSetting( 'languageLinkSiteGroup', 'wikivoyage' );

		return array(
			array( 'wikipedia', $settings, $siteStore ),
			array( 'wikivoyage', $settings2, $siteStore )
		);
	}

	/**
	 * @dataProvider getSiteGroupProvider
	 */
	public function testGetSiteGroup( $expected, SettingsArray $settings, SiteStore $siteStore ) {
		$client = new WikibaseClient( $settings, Language::factory( 'en' ), true, $siteStore );
		$this->assertEquals( $expected, $client->getSiteGroup() );
	}

	/**
	 * @return SiteStore
	 */
	public function getMockSiteStore() {
		$siteStore = new MockSiteStore();

		$site = MediaWikiSite::newFromGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );

		$siteStore->saveSite( $site );

		return $siteStore;
	}

	public function getSiteGroupProvider() {
		$settings = clone Settings::singleton();
		$settings->setSetting( 'siteGroup', null );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );

		$settings2 = clone Settings::singleton();
		$settings2->setSetting( 'siteGroup', 'wikivoyage' );
		$settings2->setSetting( 'siteGlobalID', 'enwiki' );

		$siteStore = $this->getMockSiteStore();

		return array(
			array( 'wikipedia', $settings, $siteStore ),
			array( 'wikivoyage', $settings2, $siteStore )
		);
	}

	public function testGetSite() {
		$client = $this->getDefaultInstance();
		$this->assertNotNull( $client->getSite() );
	}
}
