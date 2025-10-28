<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests;

use Exception;
use ImportStringSource;
use MediaWiki\Api\ApiMain;
use MediaWiki\Api\ApiQuerySiteinfo;
use MediaWiki\Context\DerivativeContext;
use MediaWiki\Context\RequestContext;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Skin\SkinTemplate;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserOptionsLookup;
use MediaWikiIntegrationTestCase;
use RuntimeException;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Api\EditEntity;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\RepoHooks;
use Wikibase\Repo\Store\RateLimitingIdGenerator;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\Wbui2025FeatureFlag;
use WikiImporter;

/**
 * @covers \Wikibase\Repo\RepoHooks
 *
 * @group Wikibase
 *
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
class RepoHooksTest extends MediaWikiIntegrationTestCase {

	private const FAKE_NS_ID = 4557;

	private bool $saveAllowImport = false;

	protected function setUp(): void {
		parent::setUp();

		$this->saveAllowImport = $this->getSettings()->getSetting( 'allowEntityImport' );
	}

	protected function tearDown(): void {
		$this->getSettings()->setSetting( 'allowEntityImport', $this->saveAllowImport );

		parent::tearDown();
	}

	/**
	 * @return SettingsArray
	 */
	private function getSettings() {
		return WikibaseRepo::getSettings();
	}

	public static function onBeforePageDisplayProvider(): iterable {
		$wikibaseMobileNewTermbox = [ 'wikibase.mobile', 'wikibase.termbox' ];
		$wikibaseMobileNewTermboxStyles = [ 'wikibase.termbox.styles' ];
		$wikibaseMobile = [ 'wikibase.mobile' ];

		$entityNamespaces = WikibaseRepo::getEntityNamespaceLookup()
			->getEntityNamespaces();
		$itemNamespace = $entityNamespaces['item'];

		yield 'mobile entity page' => [
			'expectedModules' => $wikibaseMobile,
			'expectedModuleStyles' => [],
			'namespace' => $itemNamespace,
			'useNewTermbox' => false,
		];
		yield 'mobile non-entity page' => [
			'expectedModules' => [],
			'expectedModuleStyles' => [],
			'namespace' => NS_TALK,
			'useNewTermbox' => false,
		];
		yield 'termbox entity page' => [
			'expectedModules' => $wikibaseMobileNewTermbox,
			'expectedModuleStyles' => $wikibaseMobileNewTermboxStyles,
			'namespace' => $itemNamespace,
			'useNewTermbox' => true,
		];
		yield 'termbox entity page with enableEntitySearchUI' => [
			'expectedModules' => array_merge(
				[ 'wikibase.ui.entitysearch' ],
				$wikibaseMobileNewTermbox
			),
			'expectedModuleStyles' => $wikibaseMobileNewTermboxStyles,
			'namespace' => $itemNamespace,
			'useNewTermbox' => true,
			'enableEntitySearchUI' => true,
		];
		yield 'termbox non-entity page' => [
			'expectedModules' => [],
			'expectedModuleStyles' => [],
			'namespace' => NS_TALK,
			'useNewTermbox' => true,
		];
		yield 'non-termbox entity page' => [
			'expectedModules' => $wikibaseMobile,
			'expectedModuleStyles' => [],
			'namespace' => self::FAKE_NS_ID,
			'useNewTermbox' => true,
		];
	}

	/**
	 * @dataProvider onBeforePageDisplayProvider
	 */
	public function testOnBeforePageDisplay(
		array $expectedModules,
		array $expectedModuleStyles,
		int $namespace,
		bool $useNewTermbox,
		bool $enableEntitySearchUI = false
	) {
		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$this->overrideMwServices(
			null,
			[ 'WikibaseRepo.MobileSite' => function () {
				return true;
			} ]
		);

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( $title );

		$outputPage = new OutputPage( $context );
		$skin = $this->createMock( SkinTemplate::class );

		$entityNamespaces = WikibaseRepo::getEntityNamespaceLookup()->getEntityNamespaces();
		$entityNamespaces += [ 'fakeEntityType' => self::FAKE_NS_ID ];
		$this->setService( 'WikibaseRepo.EntityNamespaceLookup',
			new EntityNamespaceLookup( $entityNamespaces ) );
		$settings = WikibaseRepo::getSettings();
		$settings['termboxEnabled'] = $useNewTermbox;
		$settings['enableEntitySearchUI'] = $enableEntitySearchUI;

		( new RepoHooks )->onBeforePageDisplay(
			$outputPage,
			$skin
		);

		$this->assertSame( $expectedModules, $outputPage->getModules() );
		$this->assertSame( $expectedModuleStyles, $outputPage->getModuleStyles() );
	}

	public function testOnAPIQuerySiteInfoGeneralInfo() {
		$api = $this->createMock( ApiQuerySiteinfo::class );

		$actual = [];
		( new RepoHooks )->onAPIQuerySiteInfoGeneralInfo( $api, $actual );

		foreach ( $actual['wikibase-propertytypes'] as $key => $value ) {
			$this->assertIsString( $key );
			$this->assertIsString( $value['valuetype'] );
		}

		$this->assertIsString( $actual['wikibase-conceptbaseuri'] );

		$this->assertIsString( $actual['wikibase-geoshapestoragebaseurl'] );

		$this->assertIsString( $actual['wikibase-tabulardatastoragebaseurl'] );

		if ( array_key_exists( 'wikibase-sparql', $actual ) ) {
			$this->assertIsString( $actual['wikibase-sparql'] );
		}
	}

	public static function revisionInfoProvider() {
		return [
			'empty_allowimport' => [
				[],
				true,
			],
			'empty_noimport' => [
				[],
				true,
			],
			'wikitext_allowimport' => [
				[ 'model' => CONTENT_MODEL_WIKITEXT ],
				true,
			],
			'wikitext_noimport' => [
				[ 'model' => CONTENT_MODEL_WIKITEXT ],
				false,
			],
			'item_allowimport' => [
				[ 'model' => ItemContent::CONTENT_MODEL_ID ],
				false,
				RuntimeException::class,
			],
			'item_noimport' => [
				[ 'model' => ItemContent::CONTENT_MODEL_ID ],
				true,
			],
		];
	}

	/**
	 * @dataProvider revisionInfoProvider
	 */
	public function testOnImportHandleRevisionXMLTag(
		array $revisionInfo,
		$allowEntityImport,
		$expectedException = null
	) {
		$importer = $this->createNoOpMock( WikiImporter::class );

		$this->getSettings()->setSetting( 'allowEntityImport', $allowEntityImport );

		if ( $expectedException !== null ) {
			$this->expectException( $expectedException );
		}

		( new RepoHooks )->onImportHandleRevisionXMLTag( $importer, [], $revisionInfo );
		$this->assertTrue( true ); // make PHPUnit happy
	}

	public static function importProvider() {
		return [
			'wikitext' => [ <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Bla</title><ns>0</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>Hallo Welt</text>
      <model>wikitext</model>
      <format>text/x-wiki</format>
    </revision>
  </page>
 </mediawiki>
XML
				,
				false,
			],
			'item' => [ <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Q123</title><ns>1234</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>{ "type": "item", "id":"Q123" }</text>
      <model>wikibase-item</model>
      <format>application/json</format>
    </revision>
  </page>
 </mediawiki>
XML
				,
				false,
				RuntimeException::class,
			],
			'item (allow)' => [ <<<XML
<mediawiki>
  <siteinfo>
    <sitename>TestWiki</sitename>
    <case>first-letter</case>
  </siteinfo>
  <page>
    <title>Q123</title><ns>1234</ns>
    <revision>
      <contributor><username>Tester</username><id>0</id></contributor>
      <comment>Test</comment>
      <text>{ "type": "item", "id":"Q123" }</text>
      <model>wikibase-item</model>
      <format>application/json</format>
    </revision>
  </page>
 </mediawiki>
XML
			,
				true,
			],
		];
	}

	/**
	 * @dataProvider importProvider
	 */
	public function testImportHandleRevisionXMLTag_hook( $xml, $allowImport, $expectedException = null ) {
		// WikiImporter tried to register this protocol every time, so unregister first to avoid errors.
		@stream_wrapper_unregister( 'uploadsource' );

		$this->getSettings()->setSetting( 'allowEntityImport', $allowImport );

		$source = new ImportStringSource( $xml );
		$importer = $this->getServiceContainer()->getWikiImporterFactory()
			->getWikiImporter( $source, $this->getTestSysop()->getAuthority() );

		$importer->setNoticeCallback( function() {
			// Do nothing for now. Could collect and compare notices.
		} );
		$importer->setPageOutCallback( function() {
		} );

		if ( $expectedException !== null ) {
			$this->expectException( $expectedException );
		}

		$importer->doImport();
		$this->assertTrue( true ); // make PHPUnit happy
	}

	public function testOnOutputPageParserOutput() {
		$altLinks = [ [ 'a' => 'b' ], [ 'c', 'd' ] ];

		$context = new DerivativeContext( RequestContext::getMain() );
		$outputPage = new OutputPage( $context );

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->expects( $this->exactly( 6 ) )
			->method( 'getExtensionData' )
			->willReturnCallback( function ( $key ) use ( $altLinks ) {
				if ( $key === 'wikibase-alternate-links' ) {
					return $altLinks;
				} else {
					return $key;
				}
			} );

		( new RepoHooks )->onOutputPageParserOutput( $outputPage, $parserOutput );

		$this->assertSame( TermboxView::TERMBOX_MARKUP, $outputPage->getProperty( TermboxView::TERMBOX_MARKUP ) );
		$this->assertSame( 'wikibase-view-chunks', $outputPage->getProperty( 'wikibase-view-chunks' ) );
		$this->assertSame( 'wikibase-meta-tags', $outputPage->getProperty( 'wikibase-meta-tags' ) );
		$this->assertSame( $altLinks, $outputPage->getLinkTags() );
	}

	public static function provideWbUi2025Setting() {
		yield 'wbUi2025 disabled mobile site enabled' => [ true, false, false, null, true ];
		yield 'wbUi2025 enabled mobile site disabled' => [ false, true, false, null, false ];
		yield 'wbUi2025 enabled mobile site enabled' => [ true, true, false, null, 'wbui2025' ];
		yield 'wbUi2025 disabled mobile site enabled beta feature enabled' => [ true, false, true, "1", 'wbui2025' ];
		yield 'wbUi2025 disabled mobile site enabled beta feature enabled user opted out' => [ true, false, true, "0", true ];
		yield 'wbUi2025 disabled mobile site enabled beta feature disabled user opted in' => [ true, false, false, "1", true ];
	}

	/**
	 * @dataProvider provideWbUi2025Setting
	 */
	public function testOnParserOptionsRegister(
		bool $mobileSite,
		bool $tmpMobileEditingUI,
		bool $tmpEnableMobileEditingUIBetaFeature,
		?string $userBetaOptionValue,
		bool|string $expectedWbMobileValue
	) {
		$defaults = [];
		$inCacheKey = [];
		$lazyOptions = [];
		$this->setService( 'UserOptionsLookup', $this->createConfiguredMock(
			UserOptionsLookup::class,
			[ 'getOption' => $userBetaOptionValue ]
		) );
		$this->setService( 'WikibaseRepo.MobileSite', fn() => $mobileSite );
		$this->setService( 'WikibaseRepo.Settings', new SettingsArray( [
			'tmpMobileEditingUI' => $tmpMobileEditingUI,
			'tmpEnableMobileEditingUIBetaFeature' => $tmpEnableMobileEditingUIBetaFeature,
		] ) );

		( new RepoHooks )->onParserOptionsRegister( $defaults, $inCacheKey, $lazyOptions );

		$this->assertSame( [
			'wb' => null,
			'termboxVersion' => null,
			Wbui2025FeatureFlag::PARSER_OPTION_NAME => null,
		], $defaults );
		$this->assertSame( [
			'wb' => true,
			'termboxVersion' => true,
			Wbui2025FeatureFlag::PARSER_OPTION_NAME => true,
		], $inCacheKey );
		$this->assertSame( [
			'wb',
			'termboxVersion',
			Wbui2025FeatureFlag::PARSER_OPTION_NAME,
		], array_keys( $lazyOptions ) );
		$parserOptions = $this->createConfiguredMock( ParserOptions::class, [
			'getUserIdentity' => $this->createMock( UserIdentity::class ),
		] );
		$this->assertIsCallable( $lazyOptions[ 'wb' ] );
		$this->assertSame( EntityHandler::PARSER_VERSION, $lazyOptions[ 'wb' ]() );
		$this->assertIsCallable( $lazyOptions[ 'termboxVersion' ] );
		$this->assertIsCallable( $lazyOptions[ Wbui2025FeatureFlag::PARSER_OPTION_NAME ] );
		$this->assertSame( $expectedWbMobileValue, $lazyOptions[ Wbui2025FeatureFlag::PARSER_OPTION_NAME ]( $parserOptions ) );
	}

	public function testOnParserOptionsRegister_hook() {
		$pOpts = ParserOptions::newFromAnon();

		$used = [];
		$pOpts->registerWatcher( function ( $opt ) use ( &$used ) {
			$used[$opt] = true;
		} );

		$this->assertSame( EntityHandler::PARSER_VERSION, $pOpts->getOption( 'wb' ) );
		$this->assertSame( [ 'wb' => true ], $used );
		$this->assertTrue( $pOpts->isSafeToCache() );
		$this->assertMatchesRegularExpression(
			'/(?:^|!)wb=' . EntityHandler::PARSER_VERSION . '(?:!|$)/',
			$pOpts->optionsHash( [ 'wb' ] )
		);

		$pOpts2 = ParserOptions::newFromAnon();
		$this->assertMatchesRegularExpression(
			'/(?:^|!)wb=' . EntityHandler::PARSER_VERSION . '(?:!|$)/',
			$pOpts2->optionsHash( [ 'wb' ] )
		);
	}

	public static function provideOnContentModelCanBeUsedOn() {
		// true
		yield 'Wikitext on a talk page' => [ CONTENT_MODEL_WIKITEXT, new TitleValue( NS_TALK, 'Foo' ), true ];
		yield 'Item on an item' => [ ItemContent::CONTENT_MODEL_ID, new TitleValue( WB_NS_ITEM, 'Q123' ), true ];
		yield 'Item on a talk page (not checked by this hook)' =>
			[ ItemContent::CONTENT_MODEL_ID, new TitleValue( NS_TALK, 'Foo' ), true ];
		yield 'Wikitext on a page with an entity in a slot' =>
			[ CONTENT_MODEL_WIKITEXT, new TitleValue( self::FAKE_NS_ID, 'goat' ), true ];
		// false
		yield 'Wikitext on an item' => [ CONTENT_MODEL_WIKITEXT, new TitleValue( WB_NS_ITEM, 'Q123' ), false ];
	}

	/**
	 * @dataProvider provideOnContentModelCanBeUsedOn
	 */
	public function testOnContentModelCanBeUsedOn( $contentModel, LinkTarget $linkTarget, $expectedOk ) {
		if ( $linkTarget->getNamespace() === self::FAKE_NS_ID ) {
			$this->setupTestOnContentModelCanBeUsedOn();
		}

		$ok = true;
		$return = ( new RepoHooks )->onContentModelCanBeUsedOn( $contentModel, $linkTarget, $ok );

		$this->assertSame( $expectedOk, $ok );
		$this->assertSame( $expectedOk, $return );
	}

	private function setupTestOnContentModelCanBeUsedOn() {
		// Create a fake entity type for testOnContentModelCanBeUsedOn
		$settings = WikibaseRepo::getSettings();
		$entitySources = $settings->getSetting( 'entitySources' );
		$localEntitySourceName = $settings->getSetting( 'localEntitySourceName' );
		$entitySources[$localEntitySourceName]['entityNamespaces']['slottedEntityType'] = self::FAKE_NS_ID . '/someSlot';
		$settings->setSetting( 'entitySources', $entitySources );

		$nsLookup = WikibaseRepo::getEntityNamespaceLookup();
		$type = $nsLookup->getEntityType( self::FAKE_NS_ID );
		$this->assertSame( 'slottedEntityType', $type );
		$this->assertSame( 'someSlot', $nsLookup->getEntitySlotRole( $type ) );
	}

	public function testOnMediaWikiServices() {
		global $wgNamespaceContentModels, $wgContentHandlers;

		$settings = $this->getConfVar( 'WBRepoSettings' );

		$settings['entitySources'] = [
			'local' => [
				'entityNamespaces' => [
					'item' => WB_NS_ITEM,
					'property' => WB_NS_PROPERTY . '/property',
				],
				'repoDatabase' => 'repoDb',
				'baseUri' => 'http://concept/',
				'rdfNodeNamespacePrefix' => 'wd',
				'rdfPredicateNamespacePrefix' => 'wd',
				'interwikiPrefix' => 'testwiki',
			],
		];
		$settings['localEntitySourceName'] = 'local';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );
		$this->setMwGlobals( [
			'wgNamespaceContentModels' => [],
			'wgContentHandlers' => [],
		] );

		$contentModelMappings = [
			'item' => 'wikibase-item',
			'property' => 'wikibase-property',
		];
		$this->setService(
			'WikibaseRepo.ContentModelMappings',
			$contentModelMappings
		);

		( new RepoHooks )->onMediaWikiServices( $this->getServiceContainer() );

		$this->assertSame( [ WB_NS_ITEM => 'wikibase-item' ], $wgNamespaceContentModels );
		$this->assertSame( array_values( $contentModelMappings ), array_keys( $wgContentHandlers ) );
	}

	public function testGivenLocalEntityNamespace_onNamespaceIsMovableBlocksMovingPagesInThatNamespace() {
		$itemNamespace = 120;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );

		$canBeMoved = true;

		( new RepoHooks )->onNamespaceIsMovable( $itemNamespace, $canBeMoved );

		$this->assertFalse( $canBeMoved );
	}

	public function testGivenLocalEntityInMainNamespace_onNamespaceIsMovableBlocksMovingMainNamespacePages() {
		$mainNamespace = 0;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $mainNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );

		$canBeMoved = true;

		( new RepoHooks )->onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertFalse( $canBeMoved );
	}

	public function testGivenNonLocalEntityInMainNamespace_onNamespaceIsMovableAllowsMovingPagesInMainNamespace() {
		$mainNamespace = 0;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $mainNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'props';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );

		$canBeMoved = true;

		( new RepoHooks )->onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	public function testGivenLocalEntityNamespaceAndNotMainSlot_onNamespaceIsMovableAllowsMovingPagesInThatNamespace() {
		$itemNamespace = 120;
		$itemNamespaceWithNonMainSlot = "$itemNamespace/itemslot";
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespaceWithNonMainSlot, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );

		$canBeMoved = true;

		( new RepoHooks )->onNamespaceIsMovable( $itemNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	public function testGivenWikitextInMainNamespace_onNamespaceIsMovableAllowsMovingPagesInMainNamespace() {
		$mainNamespace = 0;
		$itemNamespace = 120;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespace, $propertyNamespace );
		$settings['localEntitySourceName'] = 'items';

		$this->overrideConfigValue( 'WBRepoSettings', $settings );

		$canBeMoved = true;

		( new RepoHooks )->onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	private function newEntitySourceSettings( $itemNamespace, $propertyNamespace ) {
		$settings = $this->getConfVar( 'WBRepoSettings' );

		$settings['entitySources'] = [
			'items' => [
				'entityNamespaces' => [ 'item' => $itemNamespace ],
				'repoDatabase' => 'itemdb',
				'baseUri' => 'http://concept/',
				'rdfNodeNamespacePrefix' => 'i',
				'rdfPredicateNamespacePrefix' => 'i',
				'interwikiPrefix' => 'iwiki',
			],
			'props' => [
				'entityNamespaces' => [ 'property' => $propertyNamespace ],
				'repoDatabase' => 'propdb',
				'baseUri' => 'http://propconcept/',
				'rdfNodeNamespacePrefix' => 'p',
				'rdfPredicateNamespacePrefix' => 'p',
				'interwikiPrefix' => 'pwiki',
			],
		];

		return $settings;
	}

	public function testOnGetPreferences() {
		$preferences = [];
		$user = $this->createMock( User::class );
		( new RepoHooks )->onGetPreferences( $user, $preferences );

		$this->assertArrayHasKey( 'wb-acknowledgedcopyrightversion', $preferences );
		$this->assertArrayHasKey( 'wikibase-entitytermsview-showEntitytermslistview', $preferences );
		$this->assertArrayHasKey( 'wb-dismissleavingsitenotice', $preferences );
	}

	public function testInheritDefaultRateLimits_default() {
		$rateLimits = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'wikibase-idgenerator' => [
				'&inherit-create-edit' => 'ignored',
			],
		];
		RepoHooks::inheritDefaultRateLimits( $rateLimits );

		$expected = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'wikibase-idgenerator' => [ // like 'edit'
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
		];
		$this->assertArrayEquals( $expected, $rateLimits, false, true );
	}

	public function testInheritDefaultRateLimits_create() {
		$rateLimits = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'create' => [ // lower than 'edit'
				'ip' => [ 1, 60 ],
				'newbie' => [ 1, 60 ],
				'user' => [ 5, 60 ],
			],
			'wikibase-idgenerator' => [
				'&inherit-create-edit' => 'ignored',
			],
		];
		RepoHooks::inheritDefaultRateLimits( $rateLimits );

		$expected = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'create' => [
				'ip' => [ 1, 60 ],
				'newbie' => [ 1, 60 ],
				'user' => [ 5, 60 ],
			],
			'wikibase-idgenerator' => [ // like 'create'
				'ip' => [ 1, 60 ],
				'newbie' => [ 1, 60 ],
				'user' => [ 5, 60 ],
			],
		];
		$this->assertArrayEquals( $expected, $rateLimits, false, true );
	}

	public function testInheritDefaultRateLimits_partialOverride() {
		$rateLimits = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			// like $rateLimits['wikibase-idgenerator']['user'] = ...;
			'wikibase-idgenerator' => [
				'&inherit-create-edit' => 'ignored',
				'user' => [ 60, 60 ],
			],
		];
		RepoHooks::inheritDefaultRateLimits( $rateLimits );

		$expected = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'wikibase-idgenerator' => [
				'ip' => [ 8, 60 ], // like 'edit'
				'newbie' => [ 8, 60 ], // like 'edit'
				'user' => [ 60, 60 ], // custom value
			],
		];
		$this->assertArrayEquals( $expected, $rateLimits, false, true );
	}

	public function testInheritDefaultRateLimits_fullOverride() {
		$rateLimits = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			// like $rateLimits['wikibase-idgenerator'] = ...;
			'wikibase-idgenerator' => [
				'ip' => [ 1, 60 ],
				'newbie' => [ 1, 60 ],
				'user' => [ 5, 60 ],
			],
		];
		RepoHooks::inheritDefaultRateLimits( $rateLimits );

		$expected = [
			'edit' => [
				'ip' => [ 8, 60 ],
				'newbie' => [ 8, 60 ],
				'user' => [ 90, 60 ],
			],
			'wikibase-idgenerator' => [
				'ip' => [ 1, 60 ],
				'newbie' => [ 1, 60 ],
				'user' => [ 5, 60 ],
			],
		];
		$this->assertArrayEquals( $expected, $rateLimits, false, true );
	}

	public function testOnSkinTemplateNavigationUniversal_doesNotAlterLinksOnNonEntityContentModelPages() {
		$links = [
			'views' => [
				'edit' => 'http://foo.com/edit',
				'viewsource' => 'http://foo.com/viewsource',
			],
		];
		$expectedLinks = $links;
		$skinTemplate = $this->createMock( SkinTemplate::class );
		$title = $this->createMock( Title::class );
		$title->method( 'getContentModel' )->willReturn( CONTENT_MODEL_WIKITEXT );
		$skinTemplate->method( 'getRelevantTitle' )->willReturn( $title );

		( new RepoHooks )->onSkinTemplateNavigation__Universal( $skinTemplate, $links );
		$this->assertArrayEquals( $expectedLinks, $links );
	}

	public function testOnApiMainOnExceptionIncrementPing() {
		$this->getSettings()->setSetting( 'idGeneratorInErrorPingLimiter', 10 );
		$user = $this->createMock( User::class );
		$user->expects( $this->once() )
			->method( 'pingLimiter' )
			->with( RateLimitingIdGenerator::RATELIMIT_NAME, 10 );

		$apiModule = $this->createMock( EditEntity::class );
		$apiModule->method( 'isFreshIdAssigned' )
			->willReturn( true );

		$apiMain = $this->createMock( ApiMain::class );
		$apiMain->method( 'getModule' )
			->willReturn( $apiModule );
		$apiMain->method( 'getUser' )
			->willReturn( $user );

		( new RepoHooks )->onApiMain__onException( $apiMain, new Exception( 'foo' ) );
	}

	public function testOnApiMainOnExceptionNoop() {
		$this->getSettings()->setSetting( 'idGeneratorInErrorPingLimiter', 10 );
		$user = $this->createMock( User::class );
		$user->expects( $this->never() )
			->method( 'pingLimiter' );

		$apiModule = $this->createMock( EditEntity::class );
		$apiModule->method( 'isFreshIdAssigned' )
			->willReturn( false );

		$apiMain = $this->createMock( ApiMain::class );
		$apiMain->method( 'getModule' )
			->willReturn( $apiModule );
		$apiMain->method( 'getUser' )
			->willReturn( $user );

		( new RepoHooks )->onApiMain__onException( $apiMain, new Exception( 'foo' ) );
	}

	public function testOnApiMainOnExceptionNoopOnDisabledConfig() {
		$this->getSettings()->setSetting( 'idGeneratorInErrorPingLimiter', 0 );
		$user = $this->createMock( User::class );
		$user->expects( $this->never() )
			->method( 'pingLimiter' );

		$apiModule = $this->createMock( EditEntity::class );
		$apiModule
			->expects( $this->never() )
			->method( 'isFreshIdAssigned' );

		$apiMain = $this->createMock( ApiMain::class );
		$apiMain->method( 'getModule' )
			->willReturn( $apiModule );
		$apiMain->method( 'getUser' )
			->willReturn( $user );

		( new RepoHooks )->onApiMain__onException( $apiMain, new Exception( 'foo' ) );
	}

}
