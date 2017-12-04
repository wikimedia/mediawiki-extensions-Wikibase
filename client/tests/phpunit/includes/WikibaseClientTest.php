<?php

namespace Wikibase\Client\Tests;

use Wikibase\Lib\DataTypeFactory;
use Deserializers\Deserializer;
use HashSiteStore;
use Language;
use Serializers\Serializer;
use Site;
use SiteLookup;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\Store\ClientStore;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Client\LangLinkHandler;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\SettingsArray;
use Wikibase\StringNormalizer;

/**
 * @covers Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0+
 */
class WikibaseClientTest extends \PHPUnit_Framework_TestCase {

	public function testGetDefaultValueFormatterBuilders() {
		$first = WikibaseClient::getDefaultValueFormatterBuilders();
		$this->assertInstanceOf( WikibaseValueFormatterBuilders::class, $first );

		$second = WikibaseClient::getDefaultValueFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDefaultSnakFormatterBuilders() {
		$first = WikibaseClient::getDefaultSnakFormatterBuilders();
		$this->assertInstanceOf( WikibaseSnakFormatterBuilders::class, $first );

		$second = WikibaseClient::getDefaultSnakFormatterBuilders();
		$this->assertSame( $first, $second );
	}

	public function testGetDataTypeFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getDataTypeFactory();
		$this->assertInstanceOf( DataTypeFactory::class, $returnValue );
	}

	public function testGetEntityIdParserReturnType() {
		$returnValue = $this->getWikibaseClient()->getEntityIdParser();
		$this->assertInstanceOf( EntityIdParser::class, $returnValue );
	}

	public function testNewTermSearchInteractor() {
		$interactor = $this->getWikibaseClient()->newTermSearchInteractor( 'en' );
		$this->assertInstanceOf( TermSearchInteractor::class, $interactor );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseClient()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( PropertyDataTypeLookup::class, $returnValue );
	}

	public function testGetStringNormalizerReturnType() {
		$returnValue = $this->getWikibaseClient()->getStringNormalizer();
		$this->assertInstanceOf( StringNormalizer::class, $returnValue );
	}

	public function testNewRepoLinkerReturnType() {
		$returnValue = $this->getWikibaseClient()->newRepoLinker();
		$this->assertInstanceOf( RepoLinker::class, $returnValue );
	}

	public function testGetLanguageFallbackChainFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getLanguageFallbackChainFactory();
		$this->assertInstanceOf( LanguageFallbackChainFactory::class, $returnValue );
	}

	public function testGetLanguageFallbackLabelDescriptionLookupFactory() {
		$instance = $this->getWikibaseClient()->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->assertInstanceOf( LanguageFallbackLabelDescriptionLookupFactory::class, $instance );
	}

	public function testGetStoreReturnType() {
		$returnValue = $this->getWikibaseClient()->getStore();
		$this->assertInstanceOf( ClientStore::class, $returnValue );
	}

	public function testGetContentLanguageReturnType() {
		$returnValue = $this->getWikibaseClient()->getContentLanguage();
		$this->assertInstanceOf( Language::class, $returnValue );
	}

	public function testGetSettingsReturnType() {
		$returnValue = $this->getWikibaseClient()->getSettings();
		$this->assertInstanceOf( SettingsArray::class, $returnValue );
	}

	public function testGetSiteReturnType() {
		$returnValue = $this->getWikibaseClient()->getSite();
		$this->assertInstanceOf( Site::class, $returnValue );
	}

	public function testGetLangLinkHandlerReturnType() {
		$settings = clone WikibaseClient::getDefaultInstance()->getSettings();

		$settings->setSetting( 'siteGroup', 'wikipedia' );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', 'wikipedia' );

		$wikibaseClient = new WikibaseClient(
			$settings,
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			$this->getRepositoryDefinitions(),
			$this->getSiteLookup()
		);

		$handler = $wikibaseClient->getLangLinkHandler();
		$this->assertInstanceOf( LangLinkHandler::class, $handler );
	}

	public function testGetParserOutputDataUpdaterType() {
		$returnValue = $this->getWikibaseClient()->getParserOutputDataUpdater();
		$this->assertInstanceOf( ClientParserOutputDataUpdater::class, $returnValue );
	}

	/**
	 * @dataProvider getLangLinkSiteGroupProvider
	 */
	public function testGetLangLinkSiteGroup( $expected, SettingsArray $settings, SiteLookup $siteLookup ) {
		$client = new WikibaseClient(
			$settings,
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			$this->getRepositoryDefinitions(),
			$siteLookup
		);

		$this->assertEquals( $expected, $client->getLangLinkSiteGroup() );
	}

	public function getLangLinkSiteGroupProvider() {
		$siteLookup = $this->getSiteLookup();

		$settings = clone WikibaseClient::getDefaultInstance()->getSettings();

		$settings->setSetting( 'siteGroup', 'wikipedia' );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', null );

		$settings2 = clone $settings;
		$settings2->setSetting( 'siteGroup', 'wikipedia' );
		$settings2->setSetting( 'siteGlobalID', 'enwiki' );
		$settings2->setSetting( 'languageLinkSiteGroup', 'wikivoyage' );

		return [
			[ 'wikipedia', $settings, $siteLookup ],
			[ 'wikivoyage', $settings2, $siteLookup ]
		];
	}

	/**
	 * @dataProvider getSiteGroupProvider
	 */
	public function testGetSiteGroup( $expected, SettingsArray $settings, SiteLookup $siteLookup ) {
		$client = new WikibaseClient(
			$settings,
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			$this->getRepositoryDefinitions(),
			$siteLookup
		);

		$this->assertEquals( $expected, $client->getSiteGroup() );
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
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

		$siteLookup = $this->getSiteLookup();

		return [
			[ 'wikipedia', $settings, $siteLookup ],
			[ 'wikivoyage', $settings2, $siteLookup ]
		];
	}

	public function testGetLanguageLinkBadgeDisplay() {
		$returnValue = $this->getWikibaseClient()->getLanguageLinkBadgeDisplay();
		$this->assertInstanceOf( LanguageLinkBadgeDisplay::class, $returnValue );
	}

	public function testGetOtherProjectsSidebarGeneratorFactoryReturnType() {
		$instance = $this->getWikibaseClient()->getOtherProjectsSidebarGeneratorFactory();
		$this->assertInstanceOf( OtherProjectsSidebarGeneratorFactory::class, $instance );
	}

	public function testGetOtherProjectsSitesProvider() {
		$returnValue = $this->getWikibaseClient()->getOtherProjectsSitesProvider();
		$this->assertInstanceOf( OtherProjectsSitesProvider::class, $returnValue );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	public function testGetExternalFormatDeserializerFactory() {
		$deserializerFactory = $this->getWikibaseClient()->getBaseDataModelDeserializerFactory();
		$this->assertInstanceOf( DeserializerFactory::class, $deserializerFactory );
	}

	public function testGetInternalFormatStatementDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalFormatStatementDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testGetCompactSerializerFactory() {
		$serializerFactory = $this->getWikibaseClient()->getCompactBaseDataModelSerializerFactory();
		$this->assertInstanceOf( SerializerFactory::class, $serializerFactory );
	}

	public function testGetCompactEntitySerializer() {
		$serializer = $this->getWikibaseClient()->getCompactEntitySerializer();
		$this->assertInstanceOf( Serializer::class, $serializer );
	}

	public function testGetChangeHandler() {
		$handler = $this->getWikibaseClient()->getChangeHandler();
		$this->assertInstanceOf( ChangeHandler::class, $handler );
	}

	public function testGetParserFunctionRegistrant() {
		$registrant = $this->getWikibaseClient()->getParserFunctionRegistrant();
		$this->assertInstanceOf( ParserFunctionRegistrant::class, $registrant );
	}

	public function testGetPropertyParserFunctionRunner() {
		$runner = $this->getWikibaseClient()->getPropertyParserFunctionRunner();
		$this->assertInstanceOf( Runner::class, $runner );
	}

	public function testGetTermsLanguages() {
		$langs = $this->getWikibaseClient()->getTermsLanguages();
		$this->assertInstanceOf( ContentLanguages::class, $langs );
	}

	public function testGetRestrictedEntityLookup() {
		$restrictedEntityLookup = $this->getWikibaseClient()->getRestrictedEntityLookup();
		$this->assertInstanceOf( RestrictedEntityLookup::class, $restrictedEntityLookup );
	}

	public function testGetEntityChangeFactory() {
		$entityChangeFactory = $this->getWikibaseClient()->getEntityChangeFactory();
		$this->assertInstanceOf( EntityChangeFactory::class, $entityChangeFactory );
	}

	public function propertyOrderUrlProvider() {
		return [
			[ 'page-url' ],
			[ null ]
		];
	}

	/**
	 * @dataProvider propertyOrderUrlProvider
	 */
	public function testGetPropertyOrderProvider_noSortedPropertiesUrl( $propertyOrderUrl ) {
		$wikibaseClient = $this->getWikibaseClient();
		$wikibaseClient->getSettings()->setSetting( 'propertyOrderUrl', $propertyOrderUrl );

		$propertyOrderProvider = $wikibaseClient->getPropertyOrderProvider();
		$this->assertInstanceOf( PropertyOrderProvider::class, $propertyOrderProvider );
	}

	public function testGetDataAccessLanguageFallbackChain() {
		$lang = Language::factory( 'de' );
		$fallbackChain = $this->getWikibaseClient()->getDataAccessLanguageFallbackChain( $lang );

		$this->assertInstanceOf( LanguageFallbackChain::class, $fallbackChain );
		// "de" falls back to "en"
		$this->assertCount( 2, $fallbackChain->getFetchLanguageCodes() );
	}

	public function testGetDataAccessSnakFormatterFactory() {
		$instance = $this->getWikibaseClient()->getDataAccessSnakFormatterFactory();
		$this->assertInstanceOf( DataAccessSnakFormatterFactory::class, $instance );
	}

	public function testGetRepositoryDefinitions() {
		$repositoryDefinitions = $this->getWikibaseClient()->getRepositoryDefinitions();
		$this->assertInstanceOf( RepositoryDefinitions::class, $repositoryDefinitions );
		$this->assertEquals( $this->getRepositoryDefinitions(), $repositoryDefinitions );
	}

	public function testGetSidebarLinkBadgeDisplay() {
		$sidebarLinkBadgeDisplay = $this->getWikibaseClient()->getSidebarLinkBadgeDisplay();
		$this->assertInstanceOf( SidebarLinkBadgeDisplay::class, $sidebarLinkBadgeDisplay );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		return new WikibaseClient(
			new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			$this->getRepositoryDefinitions(),
			new HashSiteStore()
		);
	}

	/**
	 * @return RepositoryDefinitions
	 */
	private function getRepositoryDefinitions() {
		return new RepositoryDefinitions(
			[ '' => [ 'database' => '', 'base-uri' => '', 'entity-namespaces' => [], 'prefix-mapping' => [] ] ]
		);
	}

}
