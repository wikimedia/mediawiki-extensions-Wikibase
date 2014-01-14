<?php

namespace Wikibase\Test;

use Title;
use Scribunto_LuaWikibaseEntityLibrary;
use Scribunto;
use Wikibase\Settings;
use Language;

/**
 * @covers Scribunto_LuaWikibaseLibrary
 *
 * @since 0.5
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseEntityLibraryTest extends \Scribunto_LuaEngineTestBase {
	protected static $moduleName = 'LuaWikibaseEntityLibraryTests';

	function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseEntityLibraryTests' => __DIR__ . '/LuaWikibaseEntityLibraryTests.lua',
		);
	}

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}

		if ( !class_exists( 'Scribunto_LuaStandaloneEngine' ) ) {
			$this->markTestSkipped( 'test requires Scribunto' );
		}

		$this->setMwGlobals( 'wgContLang', Language::factory( 'de' ) );
	}

	public function testConstructor() {
		$engine = Scribunto::newDefaultEngine( array() );
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseEntityLibrary( $engine );
		$this->assertInstanceOf( 'Scribunto_LuaWikibaseEntityLibrary', $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
		$this->assertArrayHasKey( 'create', $package );
		$this->assertInstanceOf(
			'Scribunto_LuaStandaloneInterpreterFunction',
			$package['create']
		);
	}

	public function testGetGlobalSiteId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$expected = array( Settings::get( 'siteGlobalID' ) );
		$this->assertSame( $expected, $luaWikibaseLibrary->getGlobalSiteId() );
	}

	public function testFormatPropertyValues() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$this->assertSame(
			array( '' ),
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'P65536' )
		);
	}

	public function testFormatPropertyValuesInvalidPropertyId() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->formatPropertyValues( 'Q1', '$invalidEntityId€' );
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$engine = Scribunto::newDefaultEngine( array(
			'title' => Title::newFromText( 'Whatever' )
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseEntityLibrary( $engine );
	}

}
