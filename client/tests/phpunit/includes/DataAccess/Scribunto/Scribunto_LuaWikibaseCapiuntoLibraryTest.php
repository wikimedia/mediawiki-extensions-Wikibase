<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

if ( !class_exists( 'Capiunto\LuaLibrary' ) ) {
	class Scribunto_LuaWikibaseCapiuntoLibraryTest extends \MediaWikiTestCase {

		protected function setUp() {
			$this->markTestSkipped( 'Capiunto is not available' );
		}

	}

	return;
}

use Parser;
use ParserOptions;
use Scribunto;
use Scribunto_LuaWikibaseCapiuntoLibrary;
use Title;

/**
 * @covers Scribunto_LuaWikibaseCapiuntoLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Scribunto_LuaWikibaseCapiuntoLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseCapiuntoLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseCapiuntoLibraryTests' => __DIR__ . '/LuaWikibaseCapiuntoLibraryTests.lua',
		);
	}

	public function testConstructor() {
		$engine = Scribunto::newDefaultEngine( array() );
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseCapiuntoLibrary( $engine );
		$this->assertInstanceOf( 'Scribunto_LuaWikibaseCapiuntoLibrary', $luaWikibaseLibrary );
	}

	public function testRegister() {
		$engine = Scribunto::newDefaultEngine( array() );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
		$this->assertArrayHasKey( 'create', $package );
		$this->assertInstanceOf(
			'Scribunto_LuaStandaloneInterpreterFunction',
			$package['create']
		);
	}

	private function newScribuntoLuaWikibaseLibrary() {
		$title =  Title::newFromText( 'Nyandata!!!' );
		$parser = new Parser();
		$parser->startExternalParse( $title, new ParserOptions(), Parser::OT_HTML );

		$engine = Scribunto::newDefaultEngine( array(
			'parser' => $parser,
			'title' => $title
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseCapiuntoLibrary( $engine );
	}

}
