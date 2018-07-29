<?php

namespace Wikibase\Client\Tests\DataAccess\Scribunto;

use Language;
use LuaSandboxFunction;
use Scribunto_LuaEngine;
use Scribunto_LuaStandaloneInterpreterFunction;
use ScribuntoException;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\PropertyOrderProvider;

/**
 * @covers Wikibase\Client\DataAccess\Scribunto\Scribunto_LuaWikibaseLibrary
 *
 * @group WikibaseScribunto
 * @group WikibaseIntegration
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Lucie-Aimée Kaffee
 */
class Scribunto_LuaWikibaseLibraryTest extends Scribunto_LuaWikibaseLibraryTestCase {

	protected static $moduleName = 'LuaWikibaseLibraryTests';

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	protected function getTestModules() {
		return parent::getTestModules() + [
			'LuaWikibaseLibraryTests' => __DIR__ . '/LuaWikibaseLibraryTests.lua',
		];
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

		$this->insertPage(
			'MediaWiki:Wikibase-SortedProperties',
			"* P1\n* P22\n* P11"
		);
	}

	protected function tearDown() {
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
		$this->assertEquals( [ null ], $entity );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testGetEntity_hasLanguageFallback() {
		$this->setContentLang( 'ku-arab' );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArray = $luaWikibaseLibrary->getEntity( 'Q885588' );

		$expected = [
			[
				'id' => 'Q885588',
				'type' => 'item',
				'labels' => [
					'ku-latn' => [
						'language' => 'ku-latn',
						'value' => 'Pisîk'
					],
					'ku-arab' => [
						'language' => 'ku-arab',
						'value' => 'پسیک',
						'source-language' => 'ku-latn',
					]
				],
				'schemaVersion' => 2,
				'descriptions' => [ 'de' =>
					[
						'language' => 'de',
						'value' => 'Description of Q885588'
					]
				]
			]
		];

		$this->assertEquals( $expected, $entityArray, 'getEntity' );

		$label = $luaWikibaseLibrary->getLabel( 'Q885588' );
		$this->assertEquals( [ 'پسیک', 'ku-arab' ], $label, 'getLabel' );

		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#L.ku-arab', $usage );
	}

	public function testGetEntityInvalidIdType() {
		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( [] );
	}

	public function testGetEntityInvalidEntityId() {
		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888' );
	}

	public function entityExistsProvider() {
		return [
			[ true, 'Q885588' ],
			[ false, 'Q338380281' ]
		];
	}

	/**
	 * @dataProvider entityExistsProvider
	 */
	public function testEntityExists( $expected, $entityIdSerialization ) {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			[ $expected ],
			$luaWikibaseLibrary->entityExists( $entityIdSerialization )
		);
	}

	public function testGetEntity_entityAccessLimitExceeded() {
		$this->setExpectedException( ScribuntoException::class );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$luaWikibaseLibrary->getEntity( 'Q32487' );
		$luaWikibaseLibrary->getEntity( 'Q32488' );
		$luaWikibaseLibrary->getEntity( 'Q199024' );
	}

	public function testGetEntityId() {
		// Cache is not split, even if "allowDataAccessInUserLanguage" is true.
		$this->setAllowDataAccessInUserLanguage( true );
		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( [ null ], $entityId );
		$this->assertFalse( $cacheSplit );
	}

	public function getEntityUrlProvider() {
		return [
			'Valid ID' => [ [ 'this-is-a-URL' ], 'Q1' ],
			'Invalid ID' => [ [ null ], 'not-an-id' ]
		];
	}

	/**
	 * @dataProvider getEntityUrlProvider
	 */
	public function testGetEntityUrl( array $expected, $entityIdSerialization ) {
		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );
		$luaWikibaseLibrary->setRepoLinker( $this->getRepoLinker() );
		$result = $luaWikibaseLibrary->getEntityUrl( $entityIdSerialization );

		$this->assertSame( $expected, $result );
		$this->assertFalse( $cacheSplit );
	}

	private function getRepoLinker() {
		$repoLinker = $this->getMockBuilder( RepoLinker::class )
			->disableOriginalConstructor()
			->getMock();

		$repoLinker->expects( $this->any() )
			->method( 'getEntityUrl' )
			->with( new ItemId( 'Q1' ) )
			->will( $this->returnValue( 'this-is-a-URL' ) );

		return $repoLinker;
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testGetLabel( $allowDataAccessInUserLanguage ) {
		$this->setContentLang( 'en' );

		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary(
			$cacheSplit,
			Language::factory( 'de' )
		);
		$label = $luaWikibaseLibrary->getLabel( 'Q32487' );

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertSame(
				[ 'Lua Test Item', 'de' ],
				$label
			);
		} else {
			$this->assertSame(
				[ 'Test all the code paths', 'en' ],
				$label
			);
		}

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function provideIsValidEntityId() {
		return [
			[ true, 'Q12' ],
			[ true, 'P12' ],
			[ false, 'Q0' ],
			[ false, '[[Q2]]' ],
		];
	}

	/**
	 * @dataProvider provideIsValidEntityId
	 */
	public function testIsValidEntityId( $expected, $entityIdSerialization ) {
		$this->assertSame(
			[ $expected ],
			$this->newScribuntoLuaWikibaseLibrary()->isValidEntityId( $entityIdSerialization )
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testRenderSnak( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;
		$lang = Language::factory( 'es' );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit, $lang );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32488' );

		$snak = $entityArr[0]['claims']['P456'][1]['mainsnak'];
		$this->assertSame(
			[ 'Q885588' ],
			$luaWikibaseLibrary->renderSnak( $snak )
		);

		// When rendering the item reference in the snak,
		// track table and title usage.
		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertArrayHasKey( 'Q885588#L', $usage );
		} else {
			$this->assertArrayHasKey( 'Q885588#L.de', $usage );
		}

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function fineGrainedLuaTrackingProvider() {
		return [
			[ true, [ 'Q885588#L' ] ],
			[ false, [ 'Q32488#X', 'Q885588#L' ] ],
		];
	}

	/**
	 * @dataProvider fineGrainedLuaTrackingProvider
	 */
	public function testRenderSnak_languageFallback( $fineGrainedTracking, $expectedUsage ) {
		$this->setAllowDataAccessInUserLanguage( true );
		$this->setFineGrainedLuaTracking( $fineGrainedTracking );
		$cacheSplit = false;
		$lang = Language::factory( 'ku' );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit, $lang );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32488' );

		$snak = $entityArr[0]['claims']['P456'][1]['mainsnak'];
		$this->assertSame(
			[ 'Pisîk' ],
			$luaWikibaseLibrary->renderSnak( $snak )
		);

		// All languages in the fallback chain from 'ku' to 'ku-latn' count as "used".
		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertSame( $expectedUsage, array_keys( $usage ) );

		$this->assertSame( true, $cacheSplit );
	}

	public function testRenderSnak_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnak( [ 'a' => 'b' ] );
	}

	public function testFormatValue() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32488' );
		$snak = $entityArr[0]['claims']['P456'][1]['mainsnak'];
		$this->assertSame(
			[ '<span>Q885588</span>' ],
			$luaWikibaseLibrary->formatValue( $snak )
		);

		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#L.de', $usage );
		$this->assertArrayHasKey( 'Q885588#T', $usage );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testRenderSnaks( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;
		$lang = Language::factory( 'es' );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit, $lang );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487' );

		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$expected = [ 'A qualifier Snak, Moar qualifiers' ];
		if ( $allowDataAccessInUserLanguage ) {
			$expected = [
				$lang->commaList( [ 'A qualifier Snak', 'Moar qualifiers' ] )
			];
		}

		$this->assertSame( $expected, $luaWikibaseLibrary->renderSnaks( $snaks ) );
		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testRenderSnaks_invalidSerialization() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnaks( [ 'a' => 'b' ] );
	}

	public function testFormatValues() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487' );
		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$this->assertSame(
			[ '<span><span>A qualifier Snak</span>, <span>Moar qualifiers</span></span>' ],
			$luaWikibaseLibrary->formatValues( $snaks )
		);
	}

	public function testResolvePropertyId() {
		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary( $cacheSplit );

		$this->assertSame(
			[ 'P342' ],
			$luaWikibaseLibrary->resolvePropertyId( 'LuaTestStringProperty' )
		);
		$this->assertFalse( $cacheSplit );
	}

	public function testResolvePropertyId_propertyIdGiven() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			[ 'P342' ],
			$luaWikibaseLibrary->resolvePropertyId( 'P342' )
		);
	}

	public function testResolvePropertyId_labelNotFound() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->resolvePropertyId( 'foo' )
		);
	}

	public function provideOrderProperties() {
		return [
			'all IDs in the provider' => [
				[ 'P16', 'P5', 'P4', 'P8' ],
				[ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ],
				[ [ 1 => 'P8', 2 => 'P16', 3 => 'P4', 4 => 'P5' ] ]
			],
			'part of the IDs in the provider' => [
				[ 'P16', 'P5', 'P4', 'P8' ],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [ 1 => 'P8', 2 => 'P5', 3 => 'P16', 4 => 'P4' ] ]
			],
			'not all IDs used' => [
				[ 'P16', 'P5', 'P4' ],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [ 1 => 'P5', 2 => 'P16', 3 => 'P4' ] ]
			],
			'empty list of property ids' => [
				[],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [] ]
			]
		];
	}

	public function provideGetPropertyOrder() {
		return [
			'all IDs in the provider' => [
				[ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ],
				[ [ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ] ]
			]
		];
	}

	/**
	 * @dataProvider provideOrderProperties
	 */
	public function testOrderProperties( array $propertyIds, array $providedPropertyOrder, array $expected ) {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$luaWikibaseLibrary->setPropertyOrderProvider(
			$this->getPropertyOrderProvider( $providedPropertyOrder )
		);

		$orderedProperties = $luaWikibaseLibrary->orderProperties( $propertyIds );
		$this->assertEquals( $expected, $orderedProperties );
	}

	/**
	 * @dataProvider provideGetPropertyOrder
	 */
	public function testGetPropertyOrder( array $providedPropertyOrder, array $expected ) {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$luaWikibaseLibrary->setPropertyOrderProvider(
			$this->getPropertyOrderProvider( $providedPropertyOrder )
		);

		$propertyOrder = $luaWikibaseLibrary->getPropertyOrder();
		$this->assertEquals( $expected, $propertyOrder );
	}

	public function testGetReferencedEntityId_limitExceeded() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'referencedEntityIdAccessLimit', 2 );

		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] )
		);
		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] )
		);

		$this->setExpectedException( ScribuntoException::class );
		$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] );
	}

	public function testGetReferencedEntityId_propertyIdWrongType() {
		$luaWikibaseLibrary = $this->newScribuntoLuaWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'Q2', [ 'Q3' ] )
		);
	}

	/**
	 * @param string[] $propertyOrder
	 * @return PropertyOrderProvider $propertyOrderProvider
	 */
	private function getPropertyOrderProvider( array $propertyOrder ) {
		$propertyOrderProvider = $this->getMock( PropertyOrderProvider::class );

		$propertyOrderProvider->method( 'getPropertyOrder' )
			->willReturn( $propertyOrder );

		return $propertyOrderProvider;
	}

	/**
	 * @param bool &$cacheSplit Will become true when the ParserCache has been split
	 * @param Language|null $userLang The user's language
	 *
	 * @return Scribunto_LuaWikibaseLibrary
	 */
	private function newScribuntoLuaWikibaseLibrary( &$cacheSplit = false, Language $userLang = null ) {
		/* @var $engine Scribunto_LuaEngine */
		$engine = $this->getEngine();
		$engine->load();

		$parserOptions = $engine->getParser()->getOptions();
		if ( $userLang ) {
			$parserOptions->setUserLang( $userLang );
		}
		$parserOptions->registerWatcher(
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

	/**
	 * @param bool $value
	 */
	private function setFineGrainedLuaTracking( $value ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'fineGrainedLuaTracking', $value );
	}

}
