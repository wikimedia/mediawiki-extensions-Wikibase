<?php

namespace Wikibase\Test;

use Scribunto;
use Scribunto_LuaWikibaseEntityLibrary;
use Title;
use Wikibase\Client\Scribunto\Test\Scribunto_LuaWikibaseLibraryTestCase;
use Wikibase\Client\WikibaseClient;

/**
 * @covers Scribunto_LuaWikibaseEntityLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseEntityLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseEntityLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseEntityLibraryTests' => __DIR__ . '/LuaWikibaseEntityLibraryTests.lua',
		);
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
		$expected = array(
			WikibaseClient::getDefaultInstance()->getSettings()->getSetting( 'siteGlobalID' )
		);
		$this->assertSame( $expected, $luaWikibaseLibrary->getGlobalSiteId() );
	}

	public function testFormatPropertyValues() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$this->assertSame(
			array( '' ),
			$luaWikibaseLibrary->formatPropertyValues( 'Q1', 'P65536', array() )
		);
	}

	public function testFormatPropertyValuesInvalidPropertyId() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->formatPropertyValues( 'Q1', '$invalidEntityId€', array() );
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$engine = Scribunto::newDefaultEngine( array(
			'title' => Title::newFromText( 'Whatever' )
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseEntityLibrary( $engine );
	}

}
