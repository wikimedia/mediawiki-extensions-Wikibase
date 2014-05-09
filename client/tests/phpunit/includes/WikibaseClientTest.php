<?php

namespace Wikibase\Test;

use Language;
use MediaWikiSite;
use SiteStore;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\SnakFormatter;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\WikibaseClient
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

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getDefaultInstance()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityIdParser', $returnValue );
	}

	public function testEntityIdLabelFormatterReturnType() {
		$returnValue = $this->getDefaultInstance()->newEntityIdLabelFormatter( 'en' );
		$this->assertInstanceOf( 'Wikibase\Lib\EntityIdLabelFormatter', $returnValue );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( 'Wikibase\Lib\PropertyDataTypeLookup', $returnValue );
	}

	public function testNewSnakFormatterReturnType() {
		$returnValue = $this->getDefaultInstance()->newSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			new FormatterOptions()
		);
		$this->assertInstanceOf( 'Wikibase\Lib\SnakFormatter', $returnValue );
	}

	public function testGetStringNormalizerReturnType() {
		$returnValue = $this->getDefaultInstance()->getStringNormalizer();
		$this->assertInstanceOf( 'Wikibase\StringNormalizer', $returnValue );
	}

	public function testNewRepoLinkerReturnType() {
		$returnValue = $this->getDefaultInstance()->newRepoLinker();
		$this->assertInstanceOf( 'Wikibase\RepoLinker', $returnValue );
	}

	public function testGetLanguageFallbackChainFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getLanguageFallbackChainFactory();
		$this->assertInstanceOf( 'Wikibase\LanguageFallbackChainFactory', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getDefaultInstance()->getStore();
		$this->assertInstanceOf( 'Wikibase\ClientStore', $returnValue );
	}

	public function testGetContentLanguageReturnType() {
		$returnValue = $this->getDefaultInstance()->getContentLanguage();
		$this->assertInstanceOf( 'Language', $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getDefaultInstance()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetSiteReturnType() {
		$returnValue = $this->getDefaultInstance()->getSite();
		$this->assertInstanceOf( 'Site', $returnValue );
	}

	/**
	 * @dataProvider getLangLinkSiteGroupProvider
	 */
	public function testGetLangLinkSiteGroup( $expected, $settings, $siteStore ) {
		$client = new WikibaseClient( $settings, Language::factory( 'en' ), $siteStore );
		$this->assertEquals( $expected, $client->getLangLinkSiteGroup() );
	}

	public function getLangLinkSiteGroupProvider() {
		$siteStore = $this->getMockSiteStore();

		$settings = clone WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', null );

		$settings2 = clone $settings;
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
		$client = new WikibaseClient( $settings, Language::factory( 'en' ), $siteStore );
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
		$settings = clone WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'siteGroup', null );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );

		$settings2 = clone $settings;
		$settings2->setSetting( 'siteGroup', 'wikivoyage' );
		$settings2->setSetting( 'siteGlobalID', 'enwiki' );

		$siteStore = $this->getMockSiteStore();

		return array(
			array( 'wikipedia', $settings, $siteStore ),
			array( 'wikivoyage', $settings2, $siteStore )
		);
	}

	public function testGetSnakFormatterFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactoryReturnType() {
		$returnValue = $this->getDefaultInstance()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetClientSiteLinkLookupReturnType() {
		$returnValue = $this->getDefaultInstance()->getClientSiteLinkLookup();
		$this->assertInstanceOf( 'Wikibase\Client\ClientSiteLinkLookup', $returnValue );
	}

	public function testGetOtherProjectsSidebarGeneratorReturnType() {
		$returnValue = $this->getDefaultInstance()->getOtherProjectsSidebarGenerator();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator', $returnValue );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getDefaultInstance() {
		return WikibaseClient::getDefaultInstance();
	}
}
