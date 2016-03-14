<?php

namespace Wikibase\Client\Tests;

use HashSiteStore;
use Language;
use RuntimeException;
use Site;
use SiteStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\Item;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\SettingsArray;

/**
 * @covers Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientTest
 * @group Database
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Thiemo Mättig
 */
class WikibaseClientTest extends \PHPUnit_Framework_TestCase {

	public function testGetDefaultValueFormatterBuilders() {
		$first = WikibaseClient::getDefaultValueFormatterBuilders();
		$this->assertInstanceOf( 'Wikibase\Lib\WikibaseValueFormatterBuilders', $first );

		$second = WikibaseClient::getDefaultValueFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDefaultSnakFormatterBuilders() {
		$first = WikibaseClient::getDefaultSnakFormatterBuilders();
		$this->assertInstanceOf( 'Wikibase\Lib\WikibaseSnakFormatterBuilders', $first );

		$second = WikibaseClient::getDefaultSnakFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getDataTypeFactory();
		$this->assertInstanceOf( 'DataTypes\DataTypeFactory', $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getWikibaseClient()->getEntityIdParser();
		$this->assertInstanceOf( 'Wikibase\DataModel\Entity\EntityIdParser', $returnValue );
	}

	public function testNewTermSearchInteractor() {
		$interactor = $this->getWikibaseClient()->newTermSearchInteractor( 'en' );
		$this->assertInstanceOf( 'Wikibase\Lib\Interactors\TermIndexSearchInteractor', $interactor );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseClient()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( 'Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup', $returnValue );
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

	public function testGetLanguageFallbackLabelDescriptionLookupFactory() {
		$returnValue = $this->getWikibaseClient()->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->assertInstanceOf( 'Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory', $returnValue );
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

	public function testGetLangLinkHandlerReturnType() {
		$settings = clone WikibaseClient::getDefaultInstance()->getSettings();

		$settings->setSetting( 'siteGroup', 'wikipedia' );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', 'wikipedia' );

		$wikibaseClient = new WikibaseClient(
			$settings,
			Language::factory( 'en' ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( array() ),
			$this->getSiteStore()
		);

		$handler = $wikibaseClient->getLangLinkHandler();
		$this->assertInstanceOf( 'Wikibase\LangLinkHandler', $handler );
	}

	public function testGetParserOutputDataUpdaterType() {
		$returnValue = $this->getWikibaseClient()->getParserOutputDataUpdater();
		$this->assertInstanceOf( 'Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater', $returnValue );
	}

	/**
	 * @dataProvider getLangLinkSiteGroupProvider
	 */
	public function testGetLangLinkSiteGroup( $expected, SettingsArray $settings, SiteStore $siteStore ) {
		$client = new WikibaseClient(
			$settings,
			Language::factory( 'en' ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( array() ),
			$siteStore
		);

		$this->assertEquals( $expected, $client->getLangLinkSiteGroup() );
	}

	public function getLangLinkSiteGroupProvider() {
		$siteStore = $this->getSiteStore();

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
		$client = new WikibaseClient(
			$settings,
			Language::factory( 'en' ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( array() ),
			$siteStore
		);

		$this->assertEquals( $expected, $client->getSiteGroup() );
	}

	/**
	 * @return SiteStore
	 */
	private function getSiteStore() {
		$siteStore = new HashSiteStore();

		$site = new Site();
		$site->setGlobalId( 'enwiki' );
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

		$siteStore = $this->getSiteStore();

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

	public function testGetSiteStore() {
		$store = $this->getWikibaseClient()->getSiteStore();
		$this->assertInstanceOf( 'SiteStore', $store );
	}

	public function testGetEntityFactory() {
		$factory = $this->getWikibaseClient()->getEntityFactory();
		$this->assertInstanceOf( 'Wikibase\EntityFactory', $factory );
		$this->assertSame( array( 'item', 'property' ), $factory->getEntityTypes() );
	}

	public function testGetOtherProjectsSidebarGeneratorFactoryReturnType() {
		$settings = $this->getWikibaseClient()->getSettings();
		$settings->setSetting( 'otherProjectsLinks', array( 'my_wiki' ) );

		$this->assertInstanceOf(
			'Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory',
			$this->getWikibaseClient()->getOtherProjectsSidebarGeneratorFactory()
		);
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

		$this->setExpectedException( RuntimeException::class );
		$codec->encodeEntity( new Item(), CONTENT_FORMAT_JSON );
	}

	public function testGetInternalFormatDeserializerFactory() {
		$deserializerFactory = $this->getWikibaseClient()->getInternalFormatDeserializerFactory();
		$this->assertInstanceOf( 'Wikibase\InternalSerialization\DeserializerFactory', $deserializerFactory );
	}

	public function testGetExternalFormatDeserializerFactory() {
		$deserializerFactory = $this->getWikibaseClient()->getExternalFormatDeserializerFactory();
		$this->assertInstanceOf( 'Wikibase\DataModel\DeserializerFactory', $deserializerFactory );
	}

	public function testGetInternalFormatEntityDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalFormatEntityDeserializer();
		$this->assertInstanceOf( 'Deserializers\Deserializer', $deserializer );
	}

	public function testGetInternalFormatStatementDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalFormatStatementDeserializer();
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
		$this->assertInstanceOf( 'Wikibase\Client\DataAccess\PropertyParserFunction\Runner', $runner );
	}

	public function testGetTermsLanguages() {
		$langs = $this->getWikibaseClient()->getTermsLanguages();
		$this->assertInstanceOf( 'Wikibase\Lib\ContentLanguages', $langs );
	}

	public function testGetRestrictedEntityLookup() {
		$restrictedEntityLookup = $this->getWikibaseClient()->getRestrictedEntityLookup();
		$this->assertInstanceOf( 'Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup', $restrictedEntityLookup );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		return new WikibaseClient(
			new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( array() ),
			new HashSiteStore()
		);
	}

}
