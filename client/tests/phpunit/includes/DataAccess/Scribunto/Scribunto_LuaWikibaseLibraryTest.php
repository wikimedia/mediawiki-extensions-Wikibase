<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Language;
use LuaSandboxFunction;
use Scribunto_LuaEngine;
use Scribunto_LuaStandaloneInterpreterFunction;
use ScribuntoException;
use Title;
use User;
use WikiPage;
use WikitextContent;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class Scribunto_LuaWikibaseLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryTests';

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	protected function getTestModules() {
		return parent::getTestModules() + array(
			'LuaWikibaseLibraryTests' => __DIR__ . '/LuaWikibaseLibraryTests.lua',
		);
	}

	/**
	 * @return int
	 */
	protected static function getEntityAccessLimit() {
		// testGetEntity_entityAccessLimitExceeded needs this to be 2
		return 2;
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
		$luaWikibaseLibrary = new Scribunto_LuaWikibaseLibrary( $engine );
		$this->assertInstanceOf( Scribunto_LuaWikibaseLibrary::class, $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertInternalType( 'array', $package );
		$this->assertArrayHasKey( 'setupInterface', $package );

		// The value of setupInterface depends on the Lua runtime in use.
		$isLuaFunction =
			( $package['setupInterface'] instanceof Scribunto_LuaStandaloneInterpreterFunction ) ||
			( $package['setupInterface'] instanceof LuaSandboxFunction );

		$this->assertTrue(
			$isLuaFunction,
			'$package[\'setupInterface\'] needs to be Scribunto_LuaStandaloneInterpreterFunction or LuaSandboxFunction'
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testGetEntity( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );
		$entity = $luaWikibaseLibrary->getEntity( 'Q888' );
		$this->assertEquals( array( null ), $entity );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testGetEntity_hasLanguageFallback() {
		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'ku-arab' )
		) );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArray = $luaWikibaseLibrary->getEntity( 'Q885588' );

		$expected = array(
			array(
				'id' => 'Q885588',
				'type' => 'item',
				'labels' => array(
					'ku-latn' => array(
						'language' => 'ku-latn',
						'value' => 'Pisîk'
					),
					'ku-arab' => array(
						'language' => 'ku-arab',
						'value' => 'پسیک',
						'source-language' => 'ku-latn',
					)
				),
				'schemaVersion' => 2,
				'descriptions' => array( 'de' =>
					array(
						'language' => 'de',
						'value' => 'Description of Q885588'
					)
				)
			)
		);

		$this->assertEquals( $expected, $entityArray, 'getEntity' );

		$label = $luaWikibaseLibrary->getLabel( 'Q885588' );
		$this->assertEquals( array( 'پسیک' ), $label, 'getLabel' );

		// All languages in the fallback chain for 'ku-arab' count as "used".
		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#L.ku', $usage );
		$this->assertArrayHasKey( 'Q885588#L.ku-arab', $usage );
		$this->assertArrayHasKey( 'Q885588#L.ku-latn', $usage );
	}

	public function testGetEntityInvalidIdType() {
		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( array() );
	}

	public function testGetEntityInvalidEntityId() {
		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888' );
	}

	public function testGetEntity_entityAccessLimitExceeded() {
		$this->setExpectedException( ScribuntoException::class );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$luaWikibaseLibrary->getEntity( 'Q32487' );
		$luaWikibaseLibrary->getEntity( 'Q32488' );
		$luaWikibaseLibrary->getEntity( 'Q199024' );
	}

	public function testGetEntityId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( array( null ), $entityId );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testGetLabel( $allowDataAccessInUserLanguage ) {
		$user = new User();
		$user->setOption( 'language', 'de' );

		$this->setMwGlobals( array(
			'wgContLang' => Language::factory( 'en' ),
			'wgUser' => $user
		) );

		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );
		$label = $luaWikibaseLibrary->getLabel( 'Q32487' );

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertSame( 'Lua Test Item', $label[0] );
		} else {
			$this->assertSame( 'Test all the code paths', $label[0] );
		}

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testRenderSnak( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32488' );

		$snak = $entityArr[0]['claims']['P456'][1]['mainsnak'];
		$this->assertSame(
			array( 'Q885588' ),
			$luaWikibaseLibrary->renderSnak( $snak )
		);

		// When rendering the item reference in the snak,
		// track table and title usage.
		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		if ( $allowDataAccessInUserLanguage ) {
			global $wgUser;

			$userLang = $wgUser->getOption( 'language' );

			$this->assertArrayHasKey( 'Q885588#L.' . $userLang, $usage );
		} else {
			$this->assertArrayHasKey( 'Q885588#L.de', $usage );
		}

		$this->assertArrayHasKey( 'Q885588#T', $usage );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testRenderSnak_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnak( array( 'a' => 'b' ) );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testRenderSnaks( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487' );

		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$expected = array( 'A qualifier Snak, Moar qualifiers' );
		if ( $allowDataAccessInUserLanguage ) {
			global $wgUser;

			$lang = Language::factory( $wgUser->getOption( 'language' ) );
			$expected = array(
				$lang->commaList( array( 'A qualifier Snak', 'Moar qualifiers' ) )
			);
		}

		$this->assertSame( $expected, $luaWikibaseLibrary->renderSnaks( $snaks ) );
		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testRenderSnaks_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnaks( array( 'a' => 'b' ) );
	}

	public function testResolvePropertyId() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			array( 'P342' ),
			$luaWikibaseLibrary->resolvePropertyId( 'LuaTestStringProperty' )
		);
	}

	public function testResolvePropertyId_propertyIdGiven() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			array( 'P342' ),
			$luaWikibaseLibrary->resolvePropertyId( 'P342' )
		);
	}

	public function testResolvePropertyId_labelNotFound() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			array( null ),
			$luaWikibaseLibrary->resolvePropertyId( 'foo' )
		);
	}

	public function orderPropertiesProvider() {
		return array(
			'all IDs in the provider' => array(
				array( 'P16', 'P5', 'P4', 'P8' ),
				"* P8 \n"
				. "*P16 \n"
				. "* P4 \n"
				. "* P5",
				array( 'P8', 'P16', 'P4', 'P5' )
			),
			'part of the IDs in the provider' => array(
				array( 'P16', 'P5', 'P4', 'P8' ),
				"* P8 \n"
				. "* P5",
				array( 'P8', 'P5', 'P16', 'P4')
			)
		);
	}

	public function getPropertyOrderProvider() {
		return array(
			'all IDs in the provider' => array(
				"* P8 \n"
				. "*P16 \n"
				. "* P4 \n"
				. "* P5",
				array( 'P8', 'P16', 'P4', 'P5' )
			)
		);
	}

	/**
	 * @dataProvider orderPropertiesProvider
	 */
	public function testOrderProperties( $propertyIds, $wikipageText, $expected ) {
		$luaWikibaseLibrary = $this->setPropertyOrderProvider( $wikipageText );

		$orderedProperties = $luaWikibaseLibrary->orderProperties( $propertyIds );
		$this->assertEquals( $expected, $orderedProperties );
	}

	/**
	 * @dataProvider getPropertyOrderProvider
	 */
	public function testGetPropertyOrder( $wikipageText, $expected ) {
		$luaWikibaseLibrary = $this->setPropertyOrderProvider( $wikipageText );
		$propertyOrder = $luaWikibaseLibrary->getPropertyOrder();

		$this->assertEquals( $expected, $propertyOrder );
	}

	/**
	 * @param string $wikipageText
	 * @return Scribunto_LuaWikibaseLibrary $luaWikibaseLibrary
	 */
	private function setPropertyOrderProvider( $wikipageText ) {
		$this->makeWikiPage( 'MediaWiki:Wikibase-SortedProperties', $wikipageText );
		$propertyOrderProvider = new WikiPagePropertyOrderProvider( Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' ) );
		
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->setPropertyOrderProvider( $propertyOrderProvider );
		return $luaWikibaseLibrary;
	}

	/**
	 * @param string $name
	 * @param string $text
	 */
	private function makeWikiPage( $name, $text ) {
		$title = Title::newFromText( $name );
		$wikiPage = WikiPage::factory( $title );
		$wikiPage->doEditContent( new WikitextContent( $text ), 'test' );
	}

	/**
	 * @param bool &$cacheSplit Will become true when the ParserCache has been split
	 *
	 * @return Scribunto_LuaWikibaseLibrary
	 */
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

		return new Scribunto_LuaWikibaseLibrary( $engine );
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( $value ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

}
