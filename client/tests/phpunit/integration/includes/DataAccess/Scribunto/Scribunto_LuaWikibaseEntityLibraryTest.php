<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use Language;
use LuaSandboxFunction;
use MediaWiki\MediaWikiServices;
use ParserOptions;
use Scribunto_LuaEngine;
use Scribunto_LuaStandaloneInterpreterFunction;
use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseEntityLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	protected static $moduleName = 'LuaWikibaseEntityLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseEntityLibraryTests' => __DIR__ . '/LuaWikibaseEntityLibraryTests.lua',
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

	public function allowDataAccessInUserLanguageProvider() {
		return [
			[ true ],
			[ false ],
		];
	}

	public function testConstructor() {
		$engine = $this->getEngine();
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseEntityLibrary( $engine );
		$this->assertInstanceOf( Scribunto_LuaWikibaseEntityLibrary::class, $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertIsArray( $package );
		$this->assertArrayHasKey( 'create', $package );

		// The value of create depends on the Lua runtime in use.
		$isLuaFunction =
			( $package['create'] instanceof Scribunto_LuaStandaloneInterpreterFunction ) ||
			( $package['create'] instanceof LuaSandboxFunction );

		$this->assertTrue(
			$isLuaFunction,
			'$package[\'create\'] needs to be Scribunto_LuaStandaloneInterpreterFunction or LuaSandboxFunction'
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatPropertyValues( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

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

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

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

		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'es' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit, $lang );

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

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

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

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

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

		$lang = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'es' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit, $lang );

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
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addLabelUsage( 'Q32488', 'he' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#L.he', $usages );
	}

	// T287704
	public function testAddLabelUsageWithoutLanguage(): void {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addLabelUsage( 'Q32488', null );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#L', $usages );
	}

	public function testAddDescriptionUsage() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addDescriptionUsage( 'Q32488', 'he' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#D.he', $usages );
	}

	// T287704
	public function testAddDescriptionUsageWithoutLanguage() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addDescriptionUsage( 'Q32488', null );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		$this->assertArrayHasKey( 'Q32488#D', $usages );
	}

	public function testAddSitelinksUsage() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addSiteLinksUsage( 'Q32488' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q32488#S', $usages );
	}

	public function testAddOtherUsage() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->addOtherUsage( 'Q32488' );
		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q32488#O', $usages );
	}

	public function testGetLuaFunctionCallTracker() {
		$luaWikibaseLibrary = TestingAccessWrapper::newFromObject(
			$this->newScribuntoLuaWikibaseLibrary()
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
			$this->newScribuntoLuaWikibaseLibrary()
		);
		$luaWikibaseLibrary->luaFunctionCallTracker = $luaFunctionCallTracker;
		$luaWikibaseLibrary->incrementStatsKey( 'a-key.suffix' );
	}

	/**
	 * @param bool &$cacheSplit Will become true when the ParserCache has been split
	 * @param Language|null $userLang The user's language
	 *
	 * @return Scribunto_LuaWikibaseEntityLibrary
	 */
	private function newScribuntoLuaWikibaseLibrary( &$cacheSplit = false, Language $userLang = null ) {
		/** @var $engine Scribunto_LuaEngine */
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

		return new Scribunto_LuaWikibaseEntityLibrary( $engine );
	}

	private function setAllowDataAccessInUserLanguage( bool $value ) {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

}
