<?php

namespace Wikibase\Client\Tests\DataAccess\ParserFunctions;

use Language;
use MediaWikiTestCase;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Tests\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\DataValue\UnmappedEntityIdValue;
use Wikibase\Test\MockClientStore;

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
 */
class StatementsParserFunctionIntegrationTest extends MediaWikiTestCase {

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	/**
	 * @var MockClientStore
	 */
	private $store;

	protected function setUp() {
		parent::setUp();

		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$store = $wikibaseClient->getStore();

		if ( !( $store instanceof MockClientStore ) ) {
			$store = new MockClientStore( 'de' );
			$wikibaseClient->overrideStore( $store );
		}

		$this->assertInstanceOf(
			MockClientStore::class,
			$wikibaseClient->getStore(),
			'Mocking the default ClientStore failed'
		);

		$this->store = $store;

		$this->setContentLang( 'de' );

		$setupHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$setupHelper->setUp();

		$this->oldAllowDataAccessInUserLanguage = $wikibaseClient->getSettings()->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
	}

	protected function tearDown() {
		parent::tearDown();

		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );
		WikibaseClient::getDefaultInstance( 'reset' );
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( $value ) {
		$settings = WikibaseClient::getDefaultInstance()->getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

	public function testStatementsParserFunction_byPropertyLabel() {
		$result = $this->parseWikitextToHtml( '{{#statements:LuaTestStringProperty}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'P342#L.de', 'Q32487#O', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_byPropertyId() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#O', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_arbitraryAccess() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q32488}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32488#O', 'Q32488#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_multipleValues() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q32489}}' );

		$this->assertSame( "<p><span><span>Lua&#160;:)</span>, <span>Lua&#160;:)</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32489#O', 'Q32489#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_arbitraryAccessNotFound() {
		$result = $this->parseWikitextToHtml( '{{#statements:P342|from=Q1234567}}' );

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q1234567#O', 'Q1234567#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_unknownEntityTypeAsValue() {
		$propertyId = new PropertyId( 'P666' );
		$property = new Property( $propertyId, null, 'wikibase-coolentity' );

		$statements = new StatementList( [
			new Statement( new PropertyValueSnak( $propertyId, new UnmappedEntityIdValue( 'X303' ) ) )
		] );
		$item = new Item( new ItemId( 'Q999' ), null, null, $statements );

		// inserting entities through site link lookup is a nasty hack needed/allowed by MockClientStore
		// TODO: use proper store etc in these tests
		$this->store->getSiteLinkLookup()->putEntity( $property );
		$this->store->getSiteLinkLookup()->putEntity( $item );

		$result = $this->parseWikitextToHtml( '{{#statements:P666|from=Q999}}' );

		$this->assertSame( "<p><span><span>X303</span></span>\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q999#O', 'Q999#C.P666' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_byNonExistent() {
		$result = $this->parseWikitextToHtml( '{{#statements:P2147483645}}' );

		$this->assertRegExp(
			'/<p.*class=".*wikibase-error.*">.*P2147483645.*<\/p>/',
			$result->getText( [ 'unwrap' => true ] )
		);

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#O' ], // 'Q32487#C.P2147483645' is not tracked, as P2147483645 doesn't exist
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testStatementsParserFunction_pageNotConnected() {
		$result = $this->parseWikitextToHtml(
			'{{#statements:P342}}',
			'A page not connected to an item'
		);

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = new ParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	/**
	 * @param string $wikiText
	 * @param string $title
	 *
	 * @return ParserOutput
	 */
	private function parseWikitextToHtml( $wikiText, $title = 'WikibaseClientDataAccessTest' ) {
		$popt = new ParserOptions( User::newFromId( 0 ), Language::factory( 'en' ) );
		$parser = new Parser( [ 'class' => 'Parser' ] );
		return $parser->parse( $wikiText, Title::newFromText( $title ), $popt, Parser::OT_HTML );
	}

}
