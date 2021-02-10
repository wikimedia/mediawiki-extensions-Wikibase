<?php

namespace Wikibase\Client\Tests\Integration\DataAccess\ParserFunctions;

use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Parser;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Tests\Integration\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikimedia\TestingAccessWrapper;

/**
 * Simple integration test for the {{#property:…}} parser function.
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
class PropertyParserFunctionIntegrationTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var bool
	 */
	private $oldAllowDataAccessInUserLanguage;

	protected function setUp(): void {
		parent::setUp();

		$wikibaseClient = WikibaseClient::getDefaultInstance( 'reset' );
		$this->maskPropertyLabelResolver( $wikibaseClient );

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

		$this->setContentLang( 'de' );

		$setupHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$setupHelper->setUp();

		$this->oldAllowDataAccessInUserLanguage = $wikibaseClient->getSettings()->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
	}

	private function maskPropertyLabelResolver( WikibaseClient $wikibaseClient ) {
		$wikibaseClient = TestingAccessWrapper::newFromObject( $wikibaseClient );

		$propertyLabelResolver = $this->createMock( PropertyLabelResolver::class );
		$propertyLabelResolver->expects( $this->any() )
			->method( 'getPropertyIdsForLabels' )
			->with( [ 'LuaTestStringProperty' ] )
			->will( $this->returnValue(
				[ 'LuaTestStringProperty' => new PropertyId( 'P342' ) ]
			) );

		$wikibaseClient->propertyLabelResolver = $propertyLabelResolver;
	}

	private function newParserOutputUsageAccumulator( ParserOutput $parserOutput ): ParserOutputUsageAccumulator {
		return new ParserOutputUsageAccumulator(
			$parserOutput,
			new EntityUsageFactory( new BasicEntityIdParser() )
		);
	}

	protected function tearDown(): void {
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

	public function testPropertyParserFunction_byPropertyLabel() {
		$result = $this->parseWikitextToHtml( '{{#property:LuaTestStringProperty}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'P342#L.de', 'Q32487#O', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_byPropertyId() {
		$result = $this->parseWikitextToHtml( '{{#property:P342}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#O', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_arbitraryAccess() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q32488}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32488#O', 'Q32488#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_multipleValues() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q32489}}' );

		$this->assertSame( "<p>Lua&#160;:), Lua&#160;:)\n</p>", $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32489#O', 'Q32489#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_arbitraryAccessNotFound() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q1234567}}' );

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q1234567#O', 'Q1234567#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_byNonExistent() {
		$result = $this->parseWikitextToHtml( '{{#property:P2147483647}}' );

		$this->assertRegExp(
			'/<p.*class=".*wikibase-error.*">.*P2147483647.*<\/p>/',
			$result->getText( [ 'unwrap' => true ] )
		);

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#O' ], // 'Q32487#C.P2147483645' is not tracked, as P2147483645 doesn't exist
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_pageNotConnected() {
		$result = $this->parseWikitextToHtml(
			'{{#property:P342}}',
			'A page not connected to an item'
		);

		$this->assertSame( '', $result->getText( [ 'unwrap' => true ] ) );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
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
		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
		return $parser->parse( $wikiText, Title::newFromText( $title ), $popt, Parser::OT_HTML );
	}

}
