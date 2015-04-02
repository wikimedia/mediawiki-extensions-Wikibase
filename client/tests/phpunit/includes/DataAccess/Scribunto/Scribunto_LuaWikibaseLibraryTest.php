<?php

namespace Wikibase\Client\Tests\Scribunto;

use Language;
use Parser;
use ParserOptions;
use Scribunto;
use Scribunto_LuaWikibaseLibrary;
use Title;

/**
 * @covers Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryTests';

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseLibraryTests' => __DIR__ . '/LuaWikibaseLibraryTests.lua',
		);
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
		$entity = $luaWikibaseLibrary->getEntity( 'Q888', false );
		$this->assertEquals( array( null ), $entity );
	}

	public function testGetEntity_hasLanguageFallback() {
		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'ku-arab' )
		) );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArray = $luaWikibaseLibrary->getEntity( 'Q885588', false );

		$expected = array(
			array(
				'id' => 'Q885588',
				'type' => 'item',
				'labels' => array(
					'ku-latn' => array(
						'language' => 'ku-latn',
						'value' => 'Pisîk'
					),
					'ku-arab' => array (
						'language' => 'ku-arab',
						'value' => 'پسیک',
						'source-language' => 'ku-latn',
					)
				),
				'schemaVersion' => 2,
			)
		);

		$this->assertEquals( $expected, $entityArray, 'getEntity' );

		$label = $luaWikibaseLibrary->getLabel( 'Q885588' );
		$this->assertEquals( array( 'پسیک' ), $label, 'getLabel' );
	}

	public function testGetEntityInvalidIdType() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( array(), false );
	}

	public function testGetEntityInvalidEntityId() {
		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888', false );
	}

	public function testGetEntityId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( array( null ), $entityId );
	}

	public function testRenderSnak() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487', false );

		$snak = $entityArr[0]['claims']['P342'][1]['qualifiers']['P342'][1];
		$this->assertSame(
			array( 'A qualifier Snak' ),
			$luaWikibaseLibrary->renderSnak( $snak )
		);
	}

	public function testRenderSnak_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary->renderSnak( array( 'a' => 'b' ) );
	}

	public function testRenderSnaks() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487', false );

		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$this->assertSame(
			array( 'A qualifier Snak, Moar qualifiers' ),
			$luaWikibaseLibrary->renderSnaks( $snaks )
		);
	}

	public function testRenderSnaks_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( 'ScribuntoException' );
		$luaWikibaseLibrary->renderSnaks( array( 'a' => 'b' ) );
	}

	public function testGetUserLang() {
		$parserOptions = new ParserOptions();
		$parserOptions->setUserLang( Language::factory( 'ru' ) );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $parserOptions );

		$self = $this;  // PHP 5.3 ...
		$cacheSplit = false;
		$parserOptions->registerWatcher(
			function( $optionName ) use ( $self, &$cacheSplit ) {
				$self->assertSame( 'userlang', $optionName );
				$cacheSplit = true;
			}
		);

		$userLang = $luaWikibaseLibrary->getUserLang();
		$this->assertSame( array( 'ru' ), $userLang );
		$this->assertTrue( $cacheSplit );
	}

	/**
	 * @param ParserOptions|null $parserOptions
	 * @return Scribunto_LuaWikibaseLibrary
	 */
	private function newScribuntoLuaWikibaseLibrary( ParserOptions $parserOptions = null ) {
		$title =  Title::newFromText( 'Whatever' );

		$parser = new Parser();
		$parser->startExternalParse(
			$title,
			$parserOptions ?: new ParserOptions(),
			Parser::OT_HTML
		);

		$engine = Scribunto::newDefaultEngine( array(
			'parser' => $parser,
			'title' => $title
		) );
		$engine->load();

		return new Scribunto_LuaWikibaseLibrary( $engine );
	}

}
