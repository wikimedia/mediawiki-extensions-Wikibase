<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Integration\DataAccess\ParserFunctions;

use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWikiIntegrationTestCase;
use Wikibase\Client\ParserOutput\ScopedParserOutputProvider;
use Wikibase\Client\Tests\Integration\DataAccess\WikibaseDataAccessTestItemSetUpHelper;
use Wikibase\Client\Tests\Mocks\MockClientStore;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\Lib\Tests\Store\MockPropertyInfoLookup;

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
 * @covers \Wikibase\Client\DataAccess\ParserFunctions\Runner
 * @covers \Wikibase\Client\Usage\ParserOutputUsageAccumulator
 */
class PropertyParserFunctionIntegrationTest extends MediaWikiIntegrationTestCase {

	private bool $oldAllowDataAccessInUserLanguage;
	private ?ScopedParserOutputProvider $parserOutputProvider = null;

	protected function setUp(): void {
		parent::setUp();

		$this->maskPropertyLabelResolver();

		$this->setService( 'WikibaseClient.PropertyInfoLookup', new MockPropertyInfoLookup() );

		$store = new MockClientStore();
		$this->setService( 'WikibaseClient.Store', $store );

		$this->setContentLang( 'de' );

		$setupHelper = new WikibaseDataAccessTestItemSetUpHelper( $store );
		$setupHelper->setUp();

		$this->oldAllowDataAccessInUserLanguage = WikibaseClient::getSettings()->getSetting( 'allowDataAccessInUserLanguage' );
		$this->setAllowDataAccessInUserLanguage( false );
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
		$this->parserOutputProvider = new ScopedParserOutputProvider( $parserOutput );
		$factory = new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
		return $factory->newFromParserOutputProvider( $this->parserOutputProvider );
	}

	protected function tearDown(): void {
		parent::tearDown();

		if ( $this->parserOutputProvider ) {
			$this->parserOutputProvider->close();
		}
		$this->setAllowDataAccessInUserLanguage( $this->oldAllowDataAccessInUserLanguage );
	}

	/**
	 * @param bool $value
	 */
	private function setAllowDataAccessInUserLanguage( $value ) {
		$settings = WikibaseClient::getSettings();
		$settings->setSetting( 'allowDataAccessInUserLanguage', $value );
	}

	public function testPropertyParserFunction_byPropertyLabel() {
		$result = $this->parseWikitextToHtml( '{{#property:LuaTestStringProperty}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'P342#L.de', 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_byPropertyId() {
		$result = $this->parseWikitextToHtml( '{{#property:P342}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32487#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_arbitraryAccess() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q32488}}' );

		$this->assertSame( "<p>Lua&#160;:)\n</p>", $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32488#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_multipleValues() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q32489}}' );

		$this->assertSame( "<p>Lua&#160;:), Lua&#160;:)\n</p>", $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q32489#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_arbitraryAccessNotFound() {
		$result = $this->parseWikitextToHtml( '{{#property:P342|from=Q1234567}}' );

		$this->assertSame( '', $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[ 'Q1234567#C.P342' ],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_byNonExistent() {
		$result = $this->parseWikitextToHtml( '{{#property:P2147483647}}' );

		$this->assertMatchesRegularExpression(
			'/<p.*class=".*wikibase-error.*">.*P2147483647.*<\/p>/',
			$result->getRawText()
		);

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[], // 'Q32487#C.P2147483645' is not tracked, as P2147483645 doesn't exist
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	public function testPropertyParserFunction_pageNotConnected() {
		$result = $this->parseWikitextToHtml(
			'{{#property:P342}}',
			'A page not connected to an item'
		);

		$this->assertSame( '', $result->getRawText() );

		$usageAccumulator = $this->newParserOutputUsageAccumulator( $result );
		$this->assertArrayEquals(
			[],
			array_keys( $usageAccumulator->getUsages() )
		);
	}

	private function parseWikitextToHtml(
		string $wikiText,
		string $title = 'WikibaseClientDataAccessTest'
	): ParserOutput {
		$popt = new ParserOptions(
			User::newFromId( 0 ),
			$this->getServiceContainer()->getLanguageFactory()->getLanguage( 'en' )
		);
		$parser = $this->getServiceContainer()->getParserFactory()->create();
		return $parser->parse( $wikiText, Title::newFromTextThrow( $title ), $popt );
	}

}
