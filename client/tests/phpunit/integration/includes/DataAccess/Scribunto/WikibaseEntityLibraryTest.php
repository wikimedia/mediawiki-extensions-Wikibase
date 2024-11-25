<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use LuaSandboxFunction;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaStandalone\LuaStandaloneInterpreterFunction;
use MediaWiki\Language\Language;
use MediaWiki\Parser\ParserOptions;
use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;
use Wikibase\Client\DataAccess\Scribunto\WikibaseEntityLibrary;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class WikibaseEntityLibraryTest extends WikibaseLibraryTestCase {

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseEntityLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseEntityLibraryTests' => __DIR__ . '/WikibaseEntityLibraryTests.lua',
		];
	}

	protected function setUp(): void {
		parent::setUp();

		$settings = WikibaseClient::getSettings();

		$this->oldAllowDataAccessInUserLanguage = $settings->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );
	}

	public static function allowDataAccessInUserLanguageProvider() {
		return [
			[ true ],
			[ false ],
		];
	}

	public function testConstructor() {
		$engine = $this->getEngine();
		$luaWikibaseLibrary = new WikibaseEntityLibrary( $engine );
		$this->assertInstanceOf( WikibaseEntityLibrary::class, $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertIsArray( $package );
		$this->assertArrayHasKey( 'create', $package );

		// The value of create depends on the Lua runtime in use.
		$isLuaFunction =
			( $package['create'] instanceof LuaStandaloneInterpreterFunction ) ||
			( $package['create'] instanceof LuaSandboxFunction );

		$this->assertTrue(
			$isLuaFunction,
			'$package[\'create\'] needs to be LuaStandaloneInterpreterFunction or LuaSandboxFunction'
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatPropertyValues( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary( $cacheSplit );

		$this->assertSame(
			[ '' ],
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'P65536', [] )
		);

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testFormatPropertyValues_noPropertyId() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();

		$this->assertSame(
			[ '' ],
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'father', [] )
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatPropertyValues_usage( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'es' );
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary( $cacheSplit, $lang );

		$this->assertSame(
			[ 'Q885588' ],
			$luaWikibaseLibrary->formatPropertyValues( 'Q32488', 'P456', null )
		);

		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertArrayHasKey( 'Q885588#L', $usages );
		} else {
			$this->assertArrayHasKey( 'Q885588#L.de', $usages );
		}
		$this->assertArrayHasKey( 'Q32488#C.P456', $usages );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatStatements( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary( $cacheSplit );

		$this->assertSame(
			[ '' ],
			$luaWikibaseLibrary->formatStatements( 'Q1', 'P65536', [] )
		);

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testFormatStatements_noPropertyId() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();

		$this->assertSame(
			[ '' ],
			$luaWikibaseLibrary->formatStatements( 'Q1', 'father', [] )
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatStatements_usage( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'es' );
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary( $cacheSplit, $lang );

		$this->assertSame(
			[ '<span><span>Q885588</span></span>' ],
			$luaWikibaseLibrary->formatStatements( 'Q32488', 'P456', null )
		);

		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#T', $usages );

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertArrayHasKey( 'Q885588#L', $usages );
		} else {
			$this->assertArrayHasKey( 'Q885588#L.de', $usages );
		}
		$this->assertArrayHasKey( 'Q32488#C.P456', $usages );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testAddLabelUsage() {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addLabelUsage( 'Q32488', 'he' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#L.he', $usages );
	}

	public function testAddLabelUsageWithoutLanguage(): void {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addLabelUsage( 'Q32488', null );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#L', $usages );
	}

	public function testAddDescriptionUsage() {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addDescriptionUsage( 'Q32488', 'he' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#D.he', $usages );
	}

	public function testAddDescriptionUsageWithoutLanguage() {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addDescriptionUsage( 'Q32488', null );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#D', $usages );
	}

	public static function addTitleOrSitelinksUsageProvider() {
		yield "request for sitelink from current site" => [ null, 'Q32488#T' ];
		yield "request for sitelink on another site" => [ "ruwiki", 'Q32488#S' ];
	}

	/**
	 * @dataProvider addTitleOrSitelinksUsageProvider
	 */
	public function testAddTitleOrSitelinksUsage( ?string $requestedSiteId, string $aspectTracked ) {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addTitleOrSiteLinksUsage( 'Q32488', $requestedSiteId );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( $aspectTracked, $usages );
	}

	public function testAddOtherUsage() {
		$luaWikibaseLibrary = $this->newWikibaseEntityLibrary();
		$luaWikibaseLibrary->addOtherUsage( 'Q32488' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q32488#O', $usages );
	}

	public function testGetLuaFunctionCallTracker() {
		$luaWikibaseLibrary = TestingAccessWrapper::newFromObject(
			$this->newWikibaseEntityLibrary()
		);

		$this->assertInstanceOf(
			LuaFunctionCallTracker::class,
			$luaWikibaseLibrary->getLuaFunctionCallTracker()
		);
	}

	public function testIncrementStatsKey() {
		$luaFunctionCallTracker = $this->createMock( LuaFunctionCallTracker::class );
		$luaFunctionCallTracker->expects( $this->once() )
			->method( 'incrementKey' )
			->with( 'a-key.suffix' );

		$luaWikibaseLibrary = TestingAccessWrapper::newFromObject(
			$this->newWikibaseEntityLibrary()
		);
		$luaWikibaseLibrary->luaFunctionCallTracker = $luaFunctionCallTracker;
		$luaWikibaseLibrary->incrementStatsKey( 'a-key.suffix' );
	}

	/**
	 * @param bool &$cacheSplit Will become true when the ParserCache has been split
	 * @param Language|null $userLang The user's language
	 */
	private function newWikibaseEntityLibrary(
		bool &$cacheSplit = false,
		?Language $userLang = null
	): WikibaseEntityLibrary {
		/** @var $engine LuaEngine */
		$engine = $this->getEngine();
		$engine->load();

		$parserOptions = $engine->getParser()->getOptions();
		if ( $userLang ) {
			$parserOptions->setUserLang( $userLang );
		}
		$parserOptions->registerWatcher(
			function( $optionName ) use ( &$cacheSplit ) {
				// We only care for options that affect the cache key (thus potentially performance)
				if ( !in_array( $optionName, ParserOptions::allCacheVaryingOptions() ) ) {
					return;
				}
				$this->assertSame( 'userlang', $optionName );
				$cacheSplit = true;
			}
		);

		return new WikibaseEntityLibrary( $engine );
	}

	private function setAllowDataAccessInUserLanguage( bool $value ) {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

}
