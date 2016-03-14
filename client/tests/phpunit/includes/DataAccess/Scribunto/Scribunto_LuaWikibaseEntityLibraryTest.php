<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use LuaSandboxFunction;
use Scribunto_LuaStandaloneInterpreterFunction;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary;
use Wikibase\Client\WikibaseClient;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseEntityLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	protected static $moduleName = 'LuaWikibaseEntityLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseEntityLibraryTests' => __DIR__ . '/LuaWikibaseEntityLibraryTests.lua',
		);
	}

	protected function setUp() {
		parent::setUp();

		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$this->oldAllowDataAccessInUserLanguage = $settings->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
	}

	protected function tearDown() {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );
	}

	public function allowDataAccessInUserLanguageProvider() {
		return array(
			array( true ),
			array( false ),
		);
	}

	public function testConstructor() {
		$engine = $this->getEngine();
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseEntityLibrary( $engine );
		$this->assertInstanceOf( Scribunto_LuaWikibaseEntityLibrary::class, $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
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
	public function testGetGlobalSiteId( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

		$expected = array(
			WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'siteGlobalID' )
		);

		$this->assertSame( $expected, $luaWikibaseLibrary->getGlobalSiteId() );
		$this->assertFalse( $cacheSplit );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatPropertyValues( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

		$this->assertSame(
			array( '' ),
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'P65536', array() )
		);

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testFormatPropertyValues_noPropertyId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			array( '' ),
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'father', array() )
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testFormatPropertyValues_usage( $allowDataAccessInUserLanguage ) {
		$cacheSplit = false;
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

		$this->assertSame(
			array( 'Q885588' ),
			$luaWikibaseLibrary->formatPropertyValues( 'Q32488', 'P456', null )
		);

		$usages = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#T', $usages );

		if ( $allowDataAccessInUserLanguage ) {
			global $wgUser;

			$userLang = $wgUser->getOption( 'language' );

			$this->assertArrayHasKey( 'Q885588#L.' . $userLang, $usages );
		} else {
			$this->assertArrayHasKey( 'Q885588#L.de', $usages );
		}

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	private function newScribuntoLuaWikibaseLibrary( &$cacheSplit = false ) {
		/* @var $engine Scribunto_LuaEngine */
		$engine = $this->getEngine();
		$engine->load();

		$engine->getParser()->getOptions()->registerWatcher(
			function( $optionName ) use ( &$cacheSplit ) {
				$this->assertSame( 'userlang', $optionName );
				$cacheSplit = true;
			}
		);

		return new Scribunto_LuaWikibaseEntityLibrary( $engine );
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( $value ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

}
