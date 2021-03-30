<?php

namespace Wikibase\Client\Tests\Integration;

use HashSiteStore;
use Language;
use MediaWiki\Http\HttpRequestFactory;
use MediaWikiIntegrationTestCase;
use ReflectionClass;
use ReflectionMethod;
use Site;
use SiteLookup;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\Runner;
use Wikibase\Client\Hooks\LangLinkHandlerFactory;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\SidebarLinkBadgeDisplay;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikimedia\Rdbms\ILoadBalancer;
use Wikimedia\Rdbms\LBFactory;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\WikibaseClient
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class WikibaseClientTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// WikibaseClient service getters should never access the database or do http requests
		// https://phabricator.wikimedia.org/T243729
		$this->disallowDBAccess();
		$this->disallowHttpAccess();

		$this->setService( 'SiteLookup', $this->getSiteLookup() );
	}

	private function disallowDBAccess() {
		$this->setService(
			'DBLoadBalancerFactory',
			function() {
				$lb = $this->createMock( ILoadBalancer::class );
				$lb->expects( $this->never() )
					->method( 'getConnection' );
				$lb->expects( $this->never() )
					->method( 'getConnectionRef' );
				$lb->expects( $this->never() )
					->method( 'getMaintenanceConnectionRef' );
				$lb->method( 'getLocalDomainID' )
					->willReturn( 'banana' );

				$lbFactory = $this->createMock( LBFactory::class );
				$lbFactory->method( 'getMainLB' )
					->willReturn( $lb );

				return $lbFactory;
			}
		);
	}

	private function disallowHttpAccess() {
		$this->setService(
			'HttpRequestFactory',
			function() {
				$factory = $this->createMock( HttpRequestFactory::class );
				$factory->expects( $this->never() )
					->method( 'create' );
				$factory->expects( $this->never() )
					->method( 'request' );
				$factory->expects( $this->never() )
					->method( 'get' );
				$factory->expects( $this->never() )
					->method( 'post' );
				return $factory;
			}
		);
	}

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

	public function testNewTermSearchInteractor() {
		$interactor = $this->getWikibaseClient()->newTermSearchInteractor( 'en' );
		$this->assertInstanceOf( TermSearchInteractor::class, $interactor );
	}

	public function testGetPropertyDataTypeLookupReturnType() {
		$returnValue = $this->getWikibaseClient()->getPropertyDataTypeLookup();
		$this->assertInstanceOf( PropertyDataTypeLookup::class, $returnValue );
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
		$returnValue = WikibaseClient::getSettings();
		$this->assertInstanceOf( SettingsArray::class, $returnValue );
	}

	public function testGetLangLinkHandlerFactoryReturnType() {
		$settings = clone WikibaseClient::getSettings();

		$settings->setSetting( 'itemAndPropertySourceName', 'test' );
		$settings->setSetting( 'siteGroup', 'wikipedia' );
		$settings->setSetting( 'siteGlobalID', 'enwiki' );
		$settings->setSetting( 'languageLinkSiteGroup', 'wikipedia' );

		$wikibaseClient = $this->getWikibaseClient( $settings );

		$factory = $wikibaseClient->getLangLinkHandlerFactory();
		$this->assertInstanceOf( LangLinkHandlerFactory::class, $factory );
	}

	public function testGetParserOutputDataUpdaterType() {
		$returnValue = $this->getWikibaseClient()->getParserOutputDataUpdater();
		$this->assertInstanceOf( ClientParserOutputDataUpdater::class, $returnValue );
	}

	/**
	 * @dataProvider getLangLinkSiteGroupProvider
	 */
	public function testGetLangLinkSiteGroup( $expected, SettingsArray $settings, SiteLookup $siteLookup ) {
		$this->setService( 'WikibaseClient.Settings', $settings );
		$client = new WikibaseClient(
			$siteLookup
		);

		$this->assertEquals( $expected, $client->getLangLinkSiteGroup() );
	}

	public function getLangLinkSiteGroupProvider() {
		$siteLookup = $this->getSiteLookup();

		$settings = clone WikibaseClient::getSettings();

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
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
		$siteStore = new HashSiteStore();

		$site = new Site();
		$site->setGlobalId( 'enwiki' );
		$site->setGroup( 'wikipedia' );

		$siteStore->saveSite( $site );

		$site = new Site();
		$site->setGlobalId( 'repo' );
		$site->setGroup( 'wikipedia' );
		$site->addInterwikiId( 'repointerwiki' );

		$siteStore->saveSite( $site );

		return $siteStore;
	}

	public function testGetLanguageLinkBadgeDisplay() {
		$returnValue = $this->getWikibaseClient()->getLanguageLinkBadgeDisplay();
		$this->assertInstanceOf( LanguageLinkBadgeDisplay::class, $returnValue );
	}

	public function testGetOtherProjectsSidebarGeneratorFactoryReturnType() {
		$instance = $this->getWikibaseClient()->getOtherProjectsSidebarGeneratorFactory();
		$this->assertInstanceOf( OtherProjectsSidebarGeneratorFactory::class, $instance );
	}

	public function testGetDefaultInstance() {
		$this->assertSame(
			WikibaseClient::getDefaultInstance(),
			WikibaseClient::getDefaultInstance() );
	}

	public function testGetChangeHandler() {
		$handler = $this->getWikibaseClient()->getChangeHandler();
		$this->assertInstanceOf( ChangeHandler::class, $handler );
	}

	public function testGetRecentChangeFactory() {
		$settings = clone WikibaseClient::getSettings();

		$settings->setSetting( 'itemAndPropertySourceName', 'localrepo' );
		$this->setService( 'WikibaseClient.Settings', $settings );

		$entityTypeDefinitions = new EntityTypeDefinitions( [] );
		$this->setService( 'WikibaseClient.EntityTypeDefinitions', $entityTypeDefinitions );
		$entitySourceDefinitions = new EntitySourceDefinitions(
			[
				new EntitySource(
					'localrepo',
					'repo',
					[ 'item' => [ 'namespaceId' => 123, 'slot' => 'main' ] ],
					'',
					'',
					'',
					'repo'
				)
			],
			$entityTypeDefinitions
		);
		$this->setService( 'WikibaseClient.EntitySourceDefinitions', $entitySourceDefinitions );
		$wikibaseClient = new WikibaseClient(
			$this->getSiteLookup()
		);

		$recentChangeFactory = $wikibaseClient->getRecentChangeFactory();
		$this->assertInstanceOf( RecentChangeFactory::class, $recentChangeFactory );

		$recentChangeFactory = TestingAccessWrapper::newFromObject( $recentChangeFactory );
		$this->assertStringStartsWith(
			'repointerwiki>',
			$recentChangeFactory->externalUsernames->addPrefix( 'TestUser' )
		);
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

	public function testGetDataAccessSnakFormatterFactory() {
		$instance = $this->getWikibaseClient()->getDataAccessSnakFormatterFactory();
		$this->assertInstanceOf( DataAccessSnakFormatterFactory::class, $instance );
	}

	public function testGetSidebarLinkBadgeDisplay() {
		$sidebarLinkBadgeDisplay = $this->getWikibaseClient()->getSidebarLinkBadgeDisplay();
		$this->assertInstanceOf( SidebarLinkBadgeDisplay::class, $sidebarLinkBadgeDisplay );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient( SettingsArray $settings = null ) {
		if ( $settings === null ) {
			$settings = clone WikibaseClient::getSettings();
			$settings->setSetting( 'itemAndPropertySourceName', 'test' );
		}
		$this->setService( 'WikibaseClient.Settings', $settings );
		$entitySourceDefinitions = $this->getEntitySourceDefinitions();
		$this->setService( 'WikibaseClient.EntitySourceDefinitions', $entitySourceDefinitions );

		return new WikibaseClient(
			$this->getSiteLookup()
		);
	}

	/**
	 * @return EntitySourceDefinitions
	 */
	private function getEntitySourceDefinitions() {
		$irrelevantItemNamespaceId = 100;
		$irrelevantItemSlotName = 'main';

		$irrelevantPropertyNamespaceId = 200;
		$irrelevantPropertySlotName = 'main';

		return new EntitySourceDefinitions(
			[ new EntitySource(
				'test',
				false,
				[
					'item' => [ 'namespaceId' => $irrelevantItemNamespaceId, 'slot' => $irrelevantItemSlotName ],
					'property' => [ 'namespaceId' => $irrelevantPropertyNamespaceId, 'slot' => $irrelevantPropertySlotName ],
				],
				'',
				'',
				'',
				''
			) ],
			new EntityTypeDefinitions( [] )
		);
	}

	public function testParameterLessFunctionCalls() {
		// Make sure (as good as we can) that all functions can be called without
		// exceptions/ fatals and nothing accesses the database or does http requests.
		$wbClient = $this->getWikibaseClient();

		$reflectionClass = new ReflectionClass( $wbClient );
		$publicMethods = $reflectionClass->getMethods( ReflectionMethod::IS_PUBLIC );

		foreach ( $publicMethods as $publicMethod ) {
			if ( $publicMethod->getNumberOfRequiredParameters() === 0 ) {
				$publicMethod->invoke( $wbClient );
			}
		}
	}

}
