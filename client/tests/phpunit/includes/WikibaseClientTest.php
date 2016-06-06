<?php

namespace Wikibase\Client\Tests;

use DataTypes\DataTypeFactory;
use Deserializers\Deserializer;
use HashSiteStore;
use Language;
use RuntimeException;
use Site;
use SiteStore;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\DataAccess\PropertyParserFunction\Runner;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\OtherProjectsSitesProvider;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\ClientStore;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\LangLinkHandler;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Interactors\TermIndexSearchInteractor;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
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
		$this->assertInstanceOf( TermIndexSearchInteractor::class, $interactor );
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
		$returnValue = $this->getWikibaseClient()->getLanguageFallbackLabelDescriptionLookupFactory();
		$this->assertInstanceOf( LanguageFallbackLabelDescriptionLookupFactory::class, $returnValue );
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

	public function testGetRepoSettingsReturnType() {
		$returnValue = $this->getWikibaseClient()->getRepoSettings();

		if ( defined( 'WB_VERSION' ) ) {
			$this->assertInstanceOf( SettingsArray::class, $returnValue );
		} else {
			$this->assertNull( $returnValue );
		}
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
			Language::factory( 'en' ),
			new DataTypeDefinitions( array() ),
			new EntityTypeDefinitions( array() ),
			$this->getSiteStore()
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
		$this->assertInstanceOf( OutputFormatSnakFormatterFactory::class, $returnValue );
	}

	public function testGetValueFormatterFactoryReturnType() {
		$returnValue = $this->getWikibaseClient()->getValueFormatterFactory();
		$this->assertInstanceOf( OutputFormatValueFormatterFactory::class, $returnValue );
	}

	public function testGetLanguageLinkBadgeDisplay() {
		$returnValue = $this->getWikibaseClient()->getLanguageLinkBadgeDisplay();
		$this->assertInstanceOf( LanguageLinkBadgeDisplay::class, $returnValue );
	}

	public function testGetSiteStore() {
		$store = $this->getWikibaseClient()->getSiteStore();
		$this->assertInstanceOf( SiteStore::class, $store );
	}

	public function testGetOtherProjectsSidebarGeneratorFactoryReturnType() {
		$settings = $this->getWikibaseClient()->getSettings();
		$settings->setSetting( 'otherProjectsLinks', array( 'my_wiki' ) );

		$this->assertInstanceOf(
			OtherProjectsSidebarGeneratorFactory::class,
			$this->getWikibaseClient()->getOtherProjectsSidebarGeneratorFactory()
		);
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

	public function testGetEntityContentDataCodec() {
		$codec = $this->getWikibaseClient()->getEntityContentDataCodec();
		$this->assertInstanceOf( EntityContentDataCodec::class, $codec );

		$this->setExpectedException( RuntimeException::class );
		$codec->encodeEntity( new Item(), CONTENT_FORMAT_JSON );
	}

	public function testGetInternalFormatDeserializerFactory() {
		$deserializerFactory = $this->getWikibaseClient()->getInternalFormatDeserializerFactory();
		$this->assertInstanceOf( InternalDeserializerFactory::class, $deserializerFactory );
	}

	public function testGetExternalFormatDeserializerFactory() {
		$deserializerFactory = $this->getWikibaseClient()->getExternalFormatDeserializerFactory();
		$this->assertInstanceOf( DeserializerFactory::class, $deserializerFactory );
	}

	public function testGetInternalFormatEntityDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalFormatEntityDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testGetInternalFormatStatementDeserializer() {
		$deserializer = $this->getWikibaseClient()->getInternalFormatStatementDeserializer();
		$this->assertInstanceOf( Deserializer::class, $deserializer );
	}

	public function testGetEntityChangeFactory() {
		$factory = $this->getWikibaseClient()->getEntityChangeFactory();
		$this->assertInstanceOf( EntityChangeFactory::class, $factory );
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

	public function testGetPropertyOrderProvider() {
		$propertyOrderProvider = $this->getWikibaseClient()->getPropertyOrderProvider();
		$this->assertInstanceOf( PropertyOrderProvider::class, $propertyOrderProvider );
	}

	public function testGetDataAccessLanguageFallbackChain() {
		$lang = Language::factory( 'de' );

		$dataAccessLanguageFallbackChain = $this->getWikibaseClient()
			->getDataAccessLanguageFallbackChain( $lang );

		$this->assertInstanceOf( LanguageFallbackChain::class, $dataAccessLanguageFallbackChain );
		$langCodes = $dataAccessLanguageFallbackChain->getFetchLanguageCodes();

		// "de" falls back to "en"
		$this->assertCount( 2, $langCodes );
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
