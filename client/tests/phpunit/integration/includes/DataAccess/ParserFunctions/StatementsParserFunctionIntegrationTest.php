<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\ParserFunctions;

use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Tests\Integration\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;

/**
 * Simple integration test for the {{#statements:…}} parser function.
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group WikibaseIntegration
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 * @covers \Wikibase\Client\Usage\ParserOutputUsageAccumulator
 * @covers \Wikibase\Client\DataAccess\ParserFunctions\Runner
 */
class StatementsParserFunctionIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var bool|null
	 */
	private $oldAllowDataAccessInUserLanguage;

	/**
	 * @var bool|null
	 */
	private $oldUseKartographerMaplinkInWikitext;

	/**
	 * @var MockClientStore
	 */
	private $store;

	protected function setUp(): void {
		parent::setUp();

		$this->maskPropertyLabelResolver();

		$store = new MockClientStore( 'de' );
		$this->setService( 'WikibaseClient.Store', $store );

		$this->store = $store;

		$this->setContentLang( 'de' );
		$this->setMwGlobals( 'wgKartographerMapServer', 'http://192.0.2.0' );

		$setupHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$setupHelper->setUp();
		$settings = WikibaseClient::getSettings();

		$this->oldAllowDataAccessInUserLanguage = $settings->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
		$this->oldUseKartographerMaplinkInWikitext = $settings->getSetting( 'useKartographerMaplinkInWikitext' );
		$settings->setSetting( 'useKartographerMaplinkInWikitext', true );
	}

	private function maskPropertyLabelResolver() {
		$propertyLabelResolver = $this->createMock( PropertyLabelResolver::class );
		$propertyLabelResolver->method( 'getPropertyIdsForLabels' )
			->with( [ 'LuaTestStringProperty' ] )
			->willReturn(
				[ 'LuaTestStringProperty' => new NumericPropertyId( 'P342' ) ]
			);

		$this->setService(
			'WikibaseClient.PropertyLabelResolver',
			$propertyLabelResolver
		);
	}

	private function newParserOutputUsageAccumulator( ParserOutput $parserOutput ): UsageAccumulator {
		$factory = new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
		return $factory->newFromParserOutput( $parserOutput );
	}

	protected function tearDown(): void {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );

		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'useKartographerMaplinkInWikitext', $this->oldUseKartographerMaplinkInWikitext );
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( $value ) {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

	public function testStatementsParserFunction_byPropertyLabel() {
		$result = $this->parseWikitextToHtml( '{{#statements:LuaTestStringProperty}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'P342#L.de', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_byPropertyId() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_arbitraryAccess() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q32488}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32488#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_multipleValues() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q32489}}' );

		$this->assertSame(
			"<p><span><span>Lua&#160;:)</span>, <span>Lua&#160;:)</span></span>\n</p>",
			$result->getText( [ 'unwrap' => true ] )
		);

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32489#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_arbitraryAccessNotFound() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q1234567}}' );

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q1234567#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_unknownEntityTypeAsValue() {
		$propertyId = new NumericPropertyId( 'P666' );
		$property = new Property( $propertyId, null, 'wikibase-coolentity' );

		$statements = new StatementList(
			new Statement( new PropertyValueSnak( $propertyId, new UnmappedEntityIdValue( 'X303' ) ) )
		);
		$item = new Item( new ItemId( 'Q999' ), null, null, $statements );

		// inserting entities through site link lookup is a nasty hack needed/allowed by MockClientStore
		// TODO: use proper store etc in these tests
		$this->store->getSiteLinkLookup()->putEntity( $property );
		$this->store->getSiteLinkLookup()->putEntity( $item );

		$result = $this->parseWikitextToHtml( '{{#statements:P666|from=Q999}}' );

		$this->assertSame( "<p><span><span>X303</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q999#C.P666' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_byNonExistent() {
		$result = $this->parseWikitextToHtml( '{{#statements:P2147483645}}' );

		$this->assertMatchesRegularExpression(
			'/<p.*class=".*wikibase-error.*">.*P2147483645.*<\/p>/',
			$result->getText( [ 'unwrap' => true ] )
		);

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[], // 'Q32487#C.P2147483645' is not tracked, as P2147483645 doesn't exist
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_pageNotConnected() {
		$result = $this->parseWikitextToHtml(
			'{{#statements:P342}}',
			'A page not connected to an item'
		);

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_maplink() {
		$this->markTestSkippedIfExtensionNotLoaded( 'Kartographer' );
		$result = $this->parseWikitextToHtml( '{{#statements:P625|from=Q32489}}' );

		$text = $result->getText( [ 'unwrap' => true ] );
		$this->assertStringContainsString( 'class="mw-kartographer-maplink"', $text );
		$this->assertStringNotContainsString( '&lt;maplink', $text );
	}

	/**
	 * @param string $wikiText
	 * @param string $title
	 *
	 * @return ParserOutput
	 */
	private function parseWikitextToHtml( $wikiText, $title = 'WikibaseClientDataAccessTest' ) {
		$popt = new ParserOptions(
			User::newFromId( 0 ),
			MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' )
		);
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		return $parser->parse( $wikiText, Title::newFromTextThrow( $title ), $popt );
	}

}
