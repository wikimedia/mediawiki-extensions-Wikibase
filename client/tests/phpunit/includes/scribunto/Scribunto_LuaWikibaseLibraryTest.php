<?php

namespace Wikibase\Test;

use Title;
use Scribunto_LuaWikibaseLibrary;
use Scribunto;
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

		if ( !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have a local site link table." );
		}

		if ( !class_exists( 'Scribunto_LuaStandaloneEngine' ) ) {
			$this->markTestSkipped( 'test requires Scribunto' );
		}
	}

	public function testConstructor() {
		$engine = Scribunto::newDefaultEngine( array() );
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseLibrary( $engine );
		$this->assertInstanceOf( 'Scribunto_LuaWikibaseLibrary', $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
		$this->assertArrayHasKey( 'setupInterface', $package );
		$this->assertInstanceOf(
			'Scribunto_LuaStandaloneInterpreterFunction',
			$package['setupInterface']
		);
	}

	public function testGetEntity() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entity = $luaWikibaseLibrary->getEntity( 'Q888' );
		$this->assertEquals( array( null ), $entity );
	}

	public function testGivenInvalidEntityId_ScribuntoExceptionIsThrown() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888' );
	}

	public function testGetEntityId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( array( null ), $entityId );
	}

	public function testGetGlobalSiteId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$expected = array( Settings::get( 'siteGlobalID' ) );
		$this->assertEquals( $expected, $luaWikibaseLibrary->getGlobalSiteId() );
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$engine = Scribunto::newDefaultEngine( array(
			'title' => Title::newFromText( 'Whatever' )
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseLibrary( $engine );
	}

}
