<?php

namespace Wikibase\Repo\Tests;

use ApiQuerySiteinfo;
use ConfigFactory;
use DerivativeContext;
use ImportStringSource;
use MediaWiki\Linker\LinkTarget;
use MediaWikiIntegrationTestCase;
use MWException;
use OutputPage;
use ParserOptions;
use ParserOutput;
use RequestContext;
use SkinTemplate;
use Title;
use TitleValue;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\ParserOutput\TermboxView;
use Wikibase\Repo\RepoHooks;
use Wikibase\Repo\WikibaseRepo;
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

	/* private */ const FAKE_NS_ID = 4557;

	private $saveAllowImport = false;

	protected function setUp(): void {
		parent::setUp();

		$this->saveAllowImport = $this->getSettings()->getSetting( 'allowEntityImport' );
	}

	protected function tearDown(): void {
		$this->getSettings()->setSetting( 'allowEntityImport', $this->saveAllowImport );
		Title::clearCaches();

		parent::tearDown();
	}

	/**
	 * @return SettingsArray
	 */
	private function getSettings() {
		return WikibaseRepo::getDefaultInstance()->getSettings();
	}

	public function onBeforePageDisplayProviderMobile() {
		$wikibaseMobileNewTermbox = [ 'wikibase.mobile', 'wikibase.termbox' ];
		$wikibaseMobileNewTermboxStyles = [ 'wikibase.termbox.styles' ];
		$wikibaseMobile = [ 'wikibase.mobile' ];

		return [
			'mobile entity page' => [
				$wikibaseMobile,
				[],
				true,
				false
			],
			'mobile non-entity page' => [
				[],
				[],
				false,
				false
			],
			'termbox entity page' => [
				$wikibaseMobileNewTermbox,
				$wikibaseMobileNewTermboxStyles,
				true,
				true
			],
			'termbox non-entity page' => [
				[],
				[],
				false,
				true
			]
		];
	}

	/**
	 * @dataProvider onBeforePageDisplayProviderMobile
	 */
	public function testOnBeforePageDisplayMobile(
		array $expectedModules,
		array $expectedModuleStyles,
		$isEntityNamespace,
		$useNewTermbox
	) {
		if ( $isEntityNamespace ) {
			$namespace = array_values( WikibaseRepo::getDefaultInstance()->getLocalEntityNamespaces() )[0];
		} else {
			$namespace = NS_TALK;
		}

		$title = $this->createMock( Title::class );
		$title->expects( $this->once() )
			->method( 'getNamespace' )
			->willReturn( $namespace );

		$context = new DerivativeContext( RequestContext::getMain() );
		$context->setTitle( $title );

		$outputPage = new OutputPage( $context );

		$skin = $this->createMock( SkinTemplate::class );
		$this->getSettings()->setSetting( 'termboxEnabled', $useNewTermbox );
		RepoHooks::onBeforePageDisplayMobile(
			$outputPage,
			$skin
		);

		$this->assertSame( $expectedModules, $outputPage->getModules() );
		$this->assertSame( $expectedModuleStyles, $outputPage->getModuleStyles() );
	}

	public function testOnAPIQuerySiteInfoGeneralInfo() {
		$api = $this->getMockBuilder( ApiQuerySiteinfo::class )
			->disableOriginalConstructor()
			->getMock();

		$actual = [];
		RepoHooks::onAPIQuerySiteInfoGeneralInfo( $api, $actual );

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

	public function revisionInfoProvider() {
		return [
			'empty_allowimport' => [
				[],
				true
			],
			'empty_noimport' => [
				[],
				true
			],
			'wikitext_allowimport' => [
				[ 'model' => CONTENT_MODEL_WIKITEXT ],
				true
			],
			'wikitext_noimport' => [
				[ 'model' => CONTENT_MODEL_WIKITEXT ],
				false
			],
			'item_allowimport' => [
				[ 'model' => ItemContent::CONTENT_MODEL_ID ],
				false,
				MWException::class
			],
			'item_noimport' => [
				[ 'model' => ItemContent::CONTENT_MODEL_ID ],
				true
			]
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
		//NOTE: class is unclear, see Bug T66657. But we don't use that object anyway.
		$importer = (object)[];

		$this->getSettings()->setSetting( 'allowEntityImport', $allowEntityImport );

		if ( $expectedException !== null ) {
			$this->expectException( $expectedException );
		}

		RepoHooks::onImportHandleRevisionXMLTag( $importer, [], $revisionInfo );
		$this->assertTrue( true ); // make PHPUnit happy
	}

	public function importProvider() {
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
				false
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
				MWException::class
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
				true
			],
		];
	}

	/**
	 * @dataProvider importProvider
	 */
	public function testImportHandleRevisionXMLTag_hook( $xml, $allowImport, $expectedException = null ) {
		// WikiImporter tried to register this protocol every time, so unregister first to avoid errors.
		\Wikimedia\suppressWarnings();
		stream_wrapper_unregister( 'uploadsource' );
		\Wikimedia\restoreWarnings();

		$this->getSettings()->setSetting( 'allowEntityImport', $allowImport );

		$source = new ImportStringSource( $xml );
		$importer = new WikiImporter( $source, ConfigFactory::getDefaultInstance()->makeConfig( 'main' ) );

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
		$out = new OutputPage( $context );

		$parserOutput = $this->createMock( ParserOutput::class );
		$parserOutput->expects( $this->exactly( 5 ) )
			->method( 'getExtensionData' )
			->will( $this->returnCallback( function ( $key ) use ( $altLinks ) {
				if ( $key === 'wikibase-alternate-links' ) {
					return $altLinks;
				} else {
					return $key;
				}
			} ) );

		RepoHooks::onOutputPageParserOutput( $out, $parserOutput );

		$this->assertSame( TermboxView::TERMBOX_MARKUP, $out->getProperty( TermboxView::TERMBOX_MARKUP ) );
		$this->assertSame( 'wikibase-view-chunks', $out->getProperty( 'wikibase-view-chunks' ) );
		$this->assertSame( 'wikibase-meta-tags', $out->getProperty( 'wikibase-meta-tags' ) );
		$this->assertSame( $altLinks, $out->getLinkTags() );
	}

	public function testOnParserOptionsRegister() {
		$defaults = [];
		$inCacheKey = [];
		$lazyOptions = [];

		RepoHooks::onParserOptionsRegister( $defaults, $inCacheKey, $lazyOptions );

		$this->assertSame( [
			'wb' => null,
			'termboxVersion' => null,
			], $defaults );
		$this->assertSame( [
			'wb' => true,
			'termboxVersion' => true,
		], $inCacheKey );
		$this->assertSame( [
			'wb',
			'termboxVersion',
		], array_keys( $lazyOptions ) );
		$this->assertIsCallable( $lazyOptions[ 'wb' ] );
		$this->assertSame( EntityHandler::PARSER_VERSION, $lazyOptions[ 'wb' ]() );
		$this->assertIsCallable( $lazyOptions[ 'termboxVersion' ] );
	}

	public function testOnParserOptionsRegister_hook() {
		$pOpts = ParserOptions::newCanonical( 'canonical' );

		$used = [];
		$pOpts->registerWatcher( function ( $opt ) use ( &$used ) {
			$used[$opt] = true;
		} );

		$this->assertSame( EntityHandler::PARSER_VERSION, $pOpts->getOption( 'wb' ) );
		$this->assertSame( [ 'wb' => true ], $used );
		$this->assertTrue( $pOpts->isSafeToCache() );
		$this->assertRegExp(
			'/(?:^|!)wb=' . preg_quote( EntityHandler::PARSER_VERSION, '/' ) . '(?:!|$)/',
			$pOpts->optionsHash( [ 'wb' ] )
		);

		$pOpts2 = ParserOptions::newCanonical( 'canonical' );
		$this->assertRegExp(
			'/(?:^|!)wb=' . preg_quote( EntityHandler::PARSER_VERSION, '/' ) . '(?:!|$)/',
			$pOpts2->optionsHash( [ 'wb' ] )
		);
	}

	public function provideOnContentModelCanBeUsedOn() {
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
		$return = RepoHooks::onContentModelCanBeUsedOn( $contentModel, $linkTarget, $ok );

		$this->assertSame( $expectedOk, $ok );
		$this->assertSame( $expectedOk, $return );
	}

	private function setupTestOnContentModelCanBeUsedOn() {
		global $wgWBRepoSettings;
		// Create a fake entity type for testOnContentModelCanBeUsedOn
		$settings = $wgWBRepoSettings;
		$settings['entityNamespaces']['slottedEntityType'] = self::FAKE_NS_ID . '/someSlot';
		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		// Reset the WikibaseRepo instance after touching config that services within depend on
		WikibaseRepo::resetClassStatics();

		// Make sure the setting was correctly set & service has been updated
		$nsLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();
		$type = $nsLookup->getEntityType( self::FAKE_NS_ID );
		$this->assertSame( 'slottedEntityType', $type );
		$this->assertSame( 'someSlot', $nsLookup->getEntitySlotRole( $type ) );
	}

	public function testGivenLocalEntityNamespace_onNamespaceIsMovableBlocksMovingPagesInThatNamespace() {
		$itemNamespace = 120;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		WikibaseRepo::resetClassStatics();

		$canBeMoved = true;

		RepoHooks::onNamespaceIsMovable( $itemNamespace, $canBeMoved );

		$this->assertFalse( $canBeMoved );
	}

	public function testGivenLocalEntityInMainNamespace_onNamespaceIsMovableBlocksMovingMainNamespacePages() {
		$mainNamespace = 0;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $mainNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		WikibaseRepo::resetClassStatics();

		$canBeMoved = true;

		RepoHooks::onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertFalse( $canBeMoved );
	}

	public function testGivenNonLocalEntityInMainNamespace_onNamespaceIsMovableAllowsMovingPagesInMainNamespace() {
		$mainNamespace = 0;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $mainNamespace, $propertyNamespace );

		$settings['localEntitySourceName'] = 'props';

		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		WikibaseRepo::resetClassStatics();

		$canBeMoved = true;

		RepoHooks::onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	public function testGivenLocalEntityNamespaceAndNotMainSlot_onNamespaceIsMovableAllowsMovingPagesInThatNamespace() {
		$itemNamespace = 120;
		$itemNamespaceWithNonMainSlot = "$itemNamespace/itemslot";
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespaceWithNonMainSlot, $propertyNamespace );

		$settings['localEntitySourceName'] = 'items';

		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		WikibaseRepo::resetClassStatics();

		$canBeMoved = true;

		RepoHooks::onNamespaceIsMovable( $itemNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	public function testGivenWikitextInMainNamespace_onNamespaceIsMovableAllowsMovingPagesInMainNamespace() {
		$mainNamespace = 0;
		$itemNamespace = 120;
		$propertyNamespace = 200;

		$settings = $this->newEntitySourceSettings( $itemNamespace, $propertyNamespace );
		$settings['localEntitySourceName'] = 'items';

		$this->setMwGlobals( 'wgWBRepoSettings', $settings );

		WikibaseRepo::resetClassStatics();

		$canBeMoved = true;

		RepoHooks::onNamespaceIsMovable( $mainNamespace, $canBeMoved );

		$this->assertTrue( $canBeMoved );
	}

	private function newEntitySourceSettings( $itemNamespace, $propertyNamespace ) {
		global $wgWBRepoSettings;

		$settings = $wgWBRepoSettings;

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
		$settings['repositories'] = [
			'items' => [
				'entityNamespaces' => [ 'item' => $itemNamespace ],
			],
			'props' => [
				'entityNamespaces' => [ 'property' => $propertyNamespace ],
			],
		];
		unset( $settings['entityNamespaces'] );

		return $settings;
	}

}
