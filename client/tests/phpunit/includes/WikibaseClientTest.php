<?php

namespace Wikibase\Client\Tests;

use Language;
use MediaWikiSite;
use SiteStore;
use ValueFormatters\FormatterOptions;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\SettingsArray;
use Wikibase\Test\MockSiteStore;

/**
 * @covers Wikibase\Client\Changes\WikibaseClient
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
		$returnValue = $this->getWikibaseClient()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getWikibaseClient()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityIdParser', $returnValue );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseClient()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\PropertyDataTypeLookup', $returnValue );
	}

	public function testGetStringNormalizerReturnType() {
		$returnValue = $this->getWikibaseClient()->getStringNormalizer();
		$this->assertInstanceOf( 'Wikibase\StringNormalizer', $returnValue );
	}

	public function testNewRepoLinkerReturnType() {
		$returnValue = $this->getWikibaseClient()->newRepoLinker();
		$this->assertInstanceOf( 'Wikibase\Client\RepoLinker', $returnValue );
	}

	public function testGetLanguageFallbackChainFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getLanguageFallbackChainFactory();
		$this->assertInstanceOf( 'Wikibase\LanguageFallbackChainFactory', $returnValue );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getWikibaseClient()->getStore();
		$this->assertInstanceOf( 'Wikibase\ClientStore', $returnValue );
	}

	public function testGetContentLanguageReturnType() {
		$returnValue = $this->getWikibaseClient()->getContentLanguage();
		$this->assertInstanceOf( 'Language', $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getWikibaseClient()->getSettings();
		$this->assertInstanceOf( 'Wikibase\SettingsArray', $returnValue );
	}

	public function testGetSiteReturnType() {
		$returnValue = $this->getWikibaseClient()->getSite();
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

		$settings->setSetting( 'siteGroup', 'wikipedia' );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', null );

		$settings2 = clone $settings;
		$settings2->setSetting( 'siteGroup', 'wikipedia' );
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
		$returnValue = $this->getWikibaseClient()->getSnakFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatSnakFormatterFactory', $returnValue );
	}

	public function testGetValueFormatterFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getValueFormatterFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\OutputFormatValueFormatterFactory', $returnValue );
	}

	public function testGetLanguageLinkBadgeDisplay() {
		$returnValue = $this->getWikibaseClient()->getLanguageLinkBadgeDisplay();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\LanguageLinkBadgeDisplay', $returnValue );
	}

	public function testGetOtherProjectsSidebarGeneratorReturnType() {
		$settings = $this->getWikibaseClient()->getSettings();

		$otherProjectsLinks = $settings->getSetting( 'otherProjectsLinks' );

		$settings->setSetting( 'otherProjectsLinks', array( 'my_wiki' ) );

		$returnValue = $this->getWikibaseClient()->getOtherProjectsSidebarGenerator();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\OtherProjectsSidebarGenerator', $returnValue );

		$settings->setSetting( 'otherProjectsLinks', $otherProjectsLinks );
	}

	public function testGetOtherProjectsSitesProvider() {
		$returnValue = $this->getWikibaseClient()->getOtherProjectsSitesProvider();
		$this->assertInstanceOf( 'Wikibase\Client\OtherProjectsSitesProvider', $returnValue );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	public function testGetEntityContentDataCodec() {
		$codec = $this->getWikibaseClient()->getEntityContentDataCodec();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\EntityContentDataCodec', $codec );

		$this->setExpectedException( 'RuntimeException' );
		$codec->encodeEntity( Item::newEmpty(), CONTENT_FORMAT_JSON );
	}

	public function testGetInternalEntityDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testGetEntityChangeFactory() {
		$factory = $this->getWikibaseClient()->getEntityChangeFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Changes\EntityChangeFactory', $factory );
	}

	public function testGetChangeHandler() {
		$handler = $this->getWikibaseClient()->getChangeHandler();
		$this->assertInstanceOf( 'Wikibase\Client\Changes\ChangeHandler', $handler );
	}

	public function testGetParserFunctionRegistrant() {
		$registrant = $this->getWikibaseClient()->getParserFunctionRegistrant();
		$this->assertInstanceOf( 'Wikibase\Client\Hooks\ParserFunctionRegistrant', $registrant );
	}

    public function testGetPropertyParserFunctionRunner() {
        $runner = $this->getWikibaseClient()->getPropertyParserFunctionRunner();
        $this->assertInstanceOf( 'Wikibase\DataAccess\PropertyParserFunction\Runner', $runner );
    }

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		$settings = new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() );
		return new WikibaseClient( $settings, Language::factory( 'en' ) );
	}

}
