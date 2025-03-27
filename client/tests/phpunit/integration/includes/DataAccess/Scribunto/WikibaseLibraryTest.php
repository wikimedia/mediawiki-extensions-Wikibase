<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\DataAccess\Scribunto;

use LuaSandboxFunction;
use MediaWiki\Extension\Scribunto\Engines\LuaCommon\LuaEngine;
use MediaWiki\Extension\Scribunto\Engines\LuaStandalone\LuaStandaloneInterpreterFunction;
use MediaWiki\Extension\Scribunto\ScribuntoException;
use MediaWiki\Language\Language;
use MediaWiki\Parser\ParserOptions;
use Wikibase\Client\DataAccess\Scribunto\LuaFunctionCallTracker;
use Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\WikibaseSettings;
use Wikimedia\TestingAccessWrapper;

/**
 * @covers \Wikibase\Client\DataAccess\Scribunto\WikibaseLibrary
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
class WikibaseLibraryTest extends WikibaseLibraryTestCase {

	/** @inheritDoc */
	protected static $moduleName = 'WikibaseLibraryTests';

	private ?bool $oldAllowDataAccessInUserLanguage;

	protected function getTestModules() {
		return parent::getTestModules() + [
			'WikibaseLibraryTests' => __DIR__ . '/WikibaseLibraryTests.lua',
		];
	}

	protected static function getEntityAccessLimit(): int {
		// testGetEntity_entityAccessLimitExceeded needs this to be 2
		return 2;
	}

	protected function setUp(): void {
		parent::setUp();

		$settings = WikibaseClient::getSettings();
		$this->oldAllowDataAccessInUserLanguage = $settings->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );

		$this->insertPage(
			'MediaWiki:Wikibase-SortedProperties',
			"* P1\n* P22\n* P11"
		);
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );
	}

	public static function allowDataAccessInUserLanguageProvider() {
		return [
			[ true ],
			[ false ],
		];
	}

	public function testConstructor() {
		$engine = $this->getEngine();
		$luaWikibaseLibrary = new WikibaseLibrary( $engine );
		$this->assertInstanceOf( WikibaseLibrary::class, $luaWikibaseLibrary );
	}

	public function testRegister() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$package = $luaWikibaseLibrary->register();

		$this->assertIsArray( $package );
		$this->assertArrayHasKey( 'setupInterface', $package );

		// The value of setupInterface depends on the Lua runtime in use.
		$isLuaFunction =
			( $package['setupInterface'] instanceof LuaStandaloneInterpreterFunction ) ||
			( $package['setupInterface'] instanceof LuaSandboxFunction );

		$this->assertTrue(
			$isLuaFunction,
			'$package[\'setupInterface\'] needs to be LuaStandaloneInterpreterFunction or LuaSandboxFunction'
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testGetEntity( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit );
		$entity = $luaWikibaseLibrary->getEntity( 'Q888' );
		$this->assertEquals( [ null ], $entity );

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testGetEntity_hasLanguageFallback() {
		$this->setContentLang( 'ku-arab' );

		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$entityArray = $luaWikibaseLibrary->getEntity( 'Q885588' );

		$expected = [
			[
				'id' => 'Q885588',
				'type' => 'item',
				'labels' => [
					'ku-latn' => [
						'language' => 'ku-latn',
						'value' => 'Pisîk',
					],
					'ku-arab' => [
						'language' => 'ku-arab',
						'value' => 'پسیک',
						'source-language' => 'ku-latn',
					],
				],
				'schemaVersion' => 2,
				'descriptions' => [ 'de' =>
					[
						'language' => 'de',
						'value' => 'Description of Q885588',
					],
				],
			],
		];

		$this->assertEquals( $expected, $entityArray, 'getEntity' );

		$label = $luaWikibaseLibrary->getLabel( 'Q885588' );
		$this->assertEquals( [ 'پسیک', 'ku-arab' ], $label, 'getLabel' );

		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertArrayHasKey( 'Q885588#L.ku-arab', $usage );
	}

	public function testGetEntityInvalidEntityId() {
		$this->expectException( ScribuntoException::class );
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$luaWikibaseLibrary->getEntity( 'X888' );
	}

	private static function getParserOutputFromRedirectUsageAccumulator( $redirectUsageAccumulator ) {
		$innerAccumulator = TestingAccessWrapper::newFromObject( $redirectUsageAccumulator )->innerUsageAccumulator;
		return TestingAccessWrapper::newFromObject( $innerAccumulator )->getParserOutput();
	}

	public function testParserOutputChangeResetsUsageAccumulator() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$libraryWithMemberAccess = TestingAccessWrapper::newFromObject( $luaWikibaseLibrary );
		$parserOutput = $libraryWithMemberAccess->getParser()->getOutput();
		$usageAccumulator = $libraryWithMemberAccess->getUsageAccumulator();
		$this->assertSame(
			$parserOutput,
			self::getParserOutputFromRedirectUsageAccumulator( $usageAccumulator ),
			"Current engine parser output should be used by usage accumulator" );
		$libraryWithMemberAccess->getParser()->resetOutput();
		$newUsageAccumulator = $luaWikibaseLibrary->getUsageAccumulator();
		$this->assertSame( $usageAccumulator, $newUsageAccumulator,
			"Usage accumulator should not be reconstructed after parser output reset" );
		$newParserOutput = $libraryWithMemberAccess->getParser()->getOutput();
		$this->assertNotSame( $newParserOutput, $parserOutput,
			"Engine should have a new parser output after a reset" );
		$this->assertSame(
			$newParserOutput,
			self::getParserOutputFromRedirectUsageAccumulator( $newUsageAccumulator ),
			"Usage accumulator should be using the new parser output" );
	}

	public static function entityExistsProvider(): array {
		return [
			[ true, 'Q885588' ],
			[ false, 'Q338380281' ],
		];
	}

	/**
	 * @dataProvider entityExistsProvider
	 */
	public function testEntityExists( $expected, $entityIdSerialization ) {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->assertSame(
			[ $expected ],
			$luaWikibaseLibrary->entityExists( $entityIdSerialization )
		);
	}

	public function testGetEntity_entityAccessLimitExceeded() {
		$this->expectException( ScribuntoException::class );

		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$luaWikibaseLibrary->getEntity( 'Q32487' );
		$luaWikibaseLibrary->getEntity( 'Q32488' );
		$luaWikibaseLibrary->getEntity( 'Q199024' );
	}

	public function testGetEntityId() {
		// Cache is not split, even if "allowDataAccessInUserLanguage" is true.
		$this->setAllowDataAccessInUserLanguage( true );
		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit );

		$entityId = $luaWikibaseLibrary->getEntityId( 'CanHazKitten123' );
		$this->assertEquals( [ null ], $entityId );
		$this->assertFalse( $cacheSplit );
	}

	public static function getEntityUrlProvider(): array {
		return [
			'Valid ID' => [ [ 'this-is-a-URL' ], 'Q1' ],
			'Invalid ID' => [ [ null ], 'not-an-id' ],
		];
	}

	/**
	 * @dataProvider getEntityUrlProvider
	 */
	public function testGetEntityUrl( array $expected, $entityIdSerialization ) {
		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit );
		$luaWikibaseLibrary->setRepoLinker( $this->getRepoLinker() );
		$result = $luaWikibaseLibrary->getEntityUrl( $entityIdSerialization );

		$this->assertSame( $expected, $result );
		$this->assertFalse( $cacheSplit );
	}

	private function getRepoLinker(): RepoLinker {
		$repoLinker = $this->createMock( RepoLinker::class );

		$repoLinker->method( 'getEntityUrl' )
			->with( new ItemId( 'Q1' ) )
			->willReturn( 'this-is-a-URL' );

		return $repoLinker;
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testGetLabel( $allowDataAccessInUserLanguage ) {
		$this->setContentLang( 'en' );

		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;

		$luaWikibaseLibrary = $this->newWikibaseLibrary(
			$cacheSplit,
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'de' )
		);
		$label = $luaWikibaseLibrary->getLabel( 'Q32487' );

		if ( $allowDataAccessInUserLanguage ) {
			$this->assertSame(
				[ 'Lua-Test-Datenobjekt', 'de' ],
				$label
			);
		} else {
			$this->assertSame(
				[ 'Lua Test Item', 'en' ],
				$label
			);
		}

		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testGetBadges() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$badges = $luaWikibaseLibrary->getBadges( 'Q32487', 'fooSiteId' );

		$this->assertSame( [ [ 1 => 'Q10001', 2 => 'Q10002' ] ], $badges );
	}

	public function testGetBadges_empty() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$badges = $luaWikibaseLibrary->getBadges( 'Q32487', 'nosuchwiki' );

		$this->assertSame( [ [] ], $badges );
	}

	public static function provideIsValidEntityId(): array {
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
			$this->newWikibaseLibrary()->isValidEntityId( $entityIdSerialization )
		);
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testRenderSnak( $allowDataAccessInUserLanguage ) {
		$this->setAllowDataAccessInUserLanguage( $allowDataAccessInUserLanguage );
		$cacheSplit = false;
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'es' );

		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit, $lang );
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

	public function testRenderSnak_languageFallback() {
		$this->setAllowDataAccessInUserLanguage( true );
		$cacheSplit = false;
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'ku' );

		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit, $lang );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32488' );

		$snak = $entityArr[0]['claims']['P456'][1]['mainsnak'];
		$this->assertSame(
			[ 'Pisîk' ],
			$luaWikibaseLibrary->renderSnak( $snak )
		);

		// All languages in the fallback chain from 'ku' to 'ku-latn' count as "used".
		$usage = $luaWikibaseLibrary->getUsageAccumulator()->getUsages();
		$this->assertSame( [ 'Q885588#L' ], array_keys( $usage ) );

		$this->assertSame( true, $cacheSplit );
	}

	public function testRenderSnak_invalidSerialization() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->expectException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnak( [ 'a' => 'b' ] );
	}

	public function testFormatValue() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
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
		$lang = $this->getServiceContainer()->getLanguageFactory()->getLanguage( 'es' );

		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit, $lang );
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487' );

		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$expected = [ 'A qualifier Snak, Moar qualifiers' ];
		if ( $allowDataAccessInUserLanguage ) {
			$expected = [
				$lang->commaList( [ 'A qualifier Snak', 'Moar qualifiers' ] ),
			];
		}

		$this->assertSame( $expected, $luaWikibaseLibrary->renderSnaks( $snaks ) );
		$this->assertSame( $allowDataAccessInUserLanguage, $cacheSplit );
	}

	public function testRenderSnaks_invalidSerialization() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->expectException( ScribuntoException::class );
		$luaWikibaseLibrary->renderSnaks( [ 'a' => 'b' ] );
	}

	public function testFormatValues() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();
		$entityArr = $luaWikibaseLibrary->getEntity( 'Q32487' );
		$snaks = $entityArr[0]['claims']['P342'][1]['qualifiers'];
		$this->assertSame(
			[ '<span><span>A qualifier Snak</span>, <span>Moar qualifiers</span></span>' ],
			$luaWikibaseLibrary->formatValues( $snaks )
		);
	}

	public function testResolvePropertyId() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$cacheSplit = false;
		$luaWikibaseLibrary = $this->newWikibaseLibrary( $cacheSplit );

		$this->assertSame(
			[ 'P342' ],
			$luaWikibaseLibrary->resolvePropertyId( 'LuaTestStringProperty' )
		);
		$this->assertFalse( $cacheSplit );
	}

	public function testResolvePropertyId_propertyIdGiven() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->assertSame(
			[ 'P342' ],
			$luaWikibaseLibrary->resolvePropertyId( 'P342' )
		);
	}

	public function testResolvePropertyId_labelNotFound() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because WikibaseClient doesn't have local term store tables." );
		}

		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->resolvePropertyId( 'foo' )
		);
	}

	public static function provideOrderProperties(): array {
		return [
			'all IDs in the provider' => [
				[ 'P16', 'P5', 'P4', 'P8' ],
				[ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ],
				[ [ 1 => 'P8', 2 => 'P16', 3 => 'P4', 4 => 'P5' ] ],
			],
			'part of the IDs in the provider' => [
				[ 'P16', 'P5', 'P4', 'P8' ],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [ 1 => 'P8', 2 => 'P5', 3 => 'P16', 4 => 'P4' ] ],
			],
			'not all IDs used' => [
				[ 'P16', 'P5', 'P4' ],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [ 1 => 'P5', 2 => 'P16', 3 => 'P4' ] ],
			],
			'empty list of property ids' => [
				[],
				[ 'P8' => 0, 'P5' => 1 ],
				[ [] ],
			],
		];
	}

	public static function provideGetPropertyOrder(): array {
		return [
			'all IDs in the provider' => [
				[ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ],
				[ [ 'P8' => 0, 'P16' => 1, 'P4' => 2, 'P5' => 3 ] ],
			],
		];
	}

	/**
	 * @dataProvider provideOrderProperties
	 */
	public function testOrderProperties( array $propertyIds, array $providedPropertyOrder, array $expected ) {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

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
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$luaWikibaseLibrary->setPropertyOrderProvider(
			$this->getPropertyOrderProvider( $providedPropertyOrder )
		);

		$propertyOrder = $luaWikibaseLibrary->getPropertyOrder();
		$this->assertEquals( $expected, $propertyOrder );
	}

	public function testGetReferencedEntityId_limitExceeded() {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'referencedEntityIdAccessLimit', 2 );

		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] )
		);
		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] )
		);

		$this->expectException( ScribuntoException::class );
		$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'P2', [ 'Q3' ] );
	}

	public function testGetReferencedEntityId_propertyIdWrongType() {
		$luaWikibaseLibrary = $this->newWikibaseLibrary();

		$this->assertSame(
			[ null ],
			$luaWikibaseLibrary->getReferencedEntityId( 'Q1', 'Q2', [ 'Q3' ] )
		);
	}

	public function testGetLuaFunctionCallTracker() {
		$luaWikibaseLibrary = TestingAccessWrapper::newFromObject(
			$this->newWikibaseLibrary()
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
			->with( 'a-key', 'wikibase' );

		$luaWikibaseLibrary = TestingAccessWrapper::newFromObject(
			$this->newWikibaseLibrary()
		);
		$luaWikibaseLibrary->luaFunctionCallTracker = $luaFunctionCallTracker;
		$luaWikibaseLibrary->incrementStatsKey( 'a-key', 'wikibase' );
	}

	/**
	 * @param string[] $propertyOrder
	 */
	private function getPropertyOrderProvider( array $propertyOrder ): PropertyOrderProvider {
		$propertyOrderProvider = $this->createMock( PropertyOrderProvider::class );

		$propertyOrderProvider->method( 'getPropertyOrder' )
			->willReturn( $propertyOrder );

		return $propertyOrderProvider;
	}

	/**
	 * @param bool &$cacheSplit Will become true when the ParserCache has been split
	 * @param Language|null $userLang The user's language
	 */
	private function newWikibaseLibrary(
		bool &$cacheSplit = false,
		?Language $userLang = null
	): WikibaseLibrary {
		/** @var $engine LuaEngine */
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

		return new WikibaseLibrary( $engine );
	}

	private function setAllowDataAccessInUserLanguage( bool $value ) {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

}
