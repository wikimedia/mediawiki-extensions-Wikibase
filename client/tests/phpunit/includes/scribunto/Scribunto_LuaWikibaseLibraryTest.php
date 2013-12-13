<?php

namespace Wikibase\Test;

use Scribunto_LuaStandaloneEngine;
use Scribunto_LuaWikibaseLibrary;
use Title;
use Wikibase\Settings;

/**
 * @covers Scribunto_LuaWikibaseLibrary
 *
 * @since 0.5
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class Scribunto_LuaWikibaseLibraryTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !class_exists( 'Scribunto_LuaStandaloneEngine' ) ) {
			$this->markTestSkipped( 'test requires Scribunto' );
		}
	}

	public function testConstructor() {
		$engine = new Scribunto_LuaStandaloneEngine( array() );
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseLibrary( $engine );
		$this->assertInstanceOf ( 'Scribunto_LuaWikibaseLibrary', $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertTrue( is_array( $package ) );
		$this->assertTrue( array_key_exists( 'setupInterface', $package ) );
		$this->assertInstanceOf(
			'Scribunto_LuaStandaloneInterpreterFunction',
			$package['setupInterface']
		);
	}

	public function testGetGlobalSiteId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$expected = array( Settings::get( 'siteGlobalID' ) );
		$this->assertEquals( $expected, $luaWikibaseLibrary->getGlobalSiteId() );
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$options = array(
			'title' => Title::newFromText( 'cat' ),
			'errorFile' => null,
			'luaPath' => null,
			'allowEnvFuncs' => false
		);

		$engine = new Scribunto_LuaStandaloneEngine( $options );
		$engine->load();

		return new Scribunto_LuaWikibaseLibrary( $engine );
	}

}
