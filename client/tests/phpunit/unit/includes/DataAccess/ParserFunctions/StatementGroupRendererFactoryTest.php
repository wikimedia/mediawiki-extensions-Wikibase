<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit\DataAccess\ParserFunctions;

use MediaWiki\Languages\LanguageConverterFactory;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use Parser;
use ParserOptions;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ParserFunctions\LanguageAwareRenderer;
use Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\ParserFunctions\VariantsAwareRenderer;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\UsageAccumulatorFactory;
use Wikibase\Client\Usage\UsageDeduplicator;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRedirectTargetLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Term\PropertyLabelResolver;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;

/**
 * @covers \Wikibase\Client\DataAccess\ParserFunctions\StatementGroupRendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupRendererFactoryTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider wikitextTypeProvider
	 */
	public function testNewRendererFromParser_forWikitextType( string $type ): void {
		$parser = $this->getParser( 'zh', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser, $type );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function wikitextTypeProvider(): iterable {
		return [
			[ DataAccessSnakFormatterFactory::TYPE_ESCAPED_PLAINTEXT ],
			[ DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT ],
		];
	}

	public function testNewRenderer_contentConversionDisabled(): void {
		$parser = $this->getParser( 'zh', 'es', false, true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function testNewRenderer_titleConversionDisabled(): void {
		$parser = $this->getParser( 'zh', 'es', false, false, true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( VariantsAwareRenderer::class, $renderer );
	}

	/**
	 * @dataProvider newRenderer_forParserFormatProvider
	 */
	public function testNewRenderer_forParserFormat( string $languageCode, int $format ): void {
		$parser = $this->getParser( $languageCode, 'es', false, false, false, $format );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function newRenderer_forParserFormatProvider(): array {
		return [
			[ 'ku', Parser::OT_PLAIN ],
			[ 'zh', Parser::OT_WIKI ],
			[ 'zh', Parser::OT_PREPROCESS ],
		];
	}

	public function testNewRenderer_forNonVariantLanguage(): void {
		$parser = $this->getParser( 'en', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function testNewRender_forVariantLanguage(): void {
		$parser = $this->getParser( 'zh' );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( VariantsAwareRenderer::class, $renderer );
	}

	/**
	 * @dataProvider provideWikitextTypes
	 */
	public function testRenderOutput( string $wikitextType, string $expectedWikitext, bool $titleUsageExpected ): void {
		$wikitext = $this->getStatementGroupRendererFactory()
			->newRendererFromParser( $this->getParser(), $wikitextType )
			->render( new ItemId( 'Q1' ), 'P1' );

		$this->assertSame( $expectedWikitext, $wikitext );
	}

	/**
	 * @dataProvider provideWikitextTypes
	 */
	public function testTitleUsageTracking( string $wikitextType, string $expectedWikitext, bool $titleUsageExpected ): void {
		$parser = $this->getParser();
		$usageAccumulator = $this->newUsageAccumulatorFactory()->newFromParserOutput( $parser->getOutput() );

		$this->getStatementGroupRendererFactory()
			->newRendererFromParser( $parser, $wikitextType )
			->render( new ItemId( 'Q1' ), 'P1' );
		$usages = $usageAccumulator->getUsages();

		$this->assertSame( $titleUsageExpected, array_key_exists( 'Q7#T', $usages ) );
	}

	public function provideWikitextTypes(): array {
		return [
			[ DataAccessSnakFormatterFactory::TYPE_ESCAPED_PLAINTEXT, 'Kittens!', false ],
			[ DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT, '<span><span>Kittens!</span></span>', true ],
		];
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testNewRenderer_usageTracking( bool $allowDataAccessInUserLanguage ): void {
		$parser = $this->getParser( 'en', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory( $allowDataAccessInUserLanguage );
		$renderer = $rendererFactory->newRendererFromParser( $parser, DataAccessSnakFormatterFactory::TYPE_RICH_WIKITEXT );

		$usageAccumulator = $this->newUsageAccumulatorFactory()->newFromParserOutput( $parser->getOutput() );

		$renderer->render( new ItemId( 'Q1' ), 'P1' );

		$usages = $usageAccumulator->getUsages();
		if ( $allowDataAccessInUserLanguage ) {
			$this->assertArrayHasKey( 'Q7#L.es', $usages );
		} else {
			$this->assertArrayHasKey( 'Q7#L.en', $usages );
		}
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testNewRendererFromParser_languageOption( bool $allowDataAccessInUserLanguage ): void {
		$labelResolver = $this->createMock( PropertyLabelResolver::class );

		$formatterFactory = $this->createMock( OutputFormatSnakFormatterFactory::class );
		$formatterFactory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->willReturnCallback(
				function( $format, FormatterOptions $options ) use ( $allowDataAccessInUserLanguage )  {
					$this->assertSame(
						$allowDataAccessInUserLanguage ? 'es' : 'de',
						$options->getOption( ValueFormatter::OPT_LANG )
					);
					return $this->createMock( SnakFormatter::class );
				}
			);

		$factory = new StatementGroupRendererFactory(
			$labelResolver,
			new SnaksFinder(),
			$this->createMock( EntityLookup::class ),
			new DataAccessSnakFormatterFactory(
				$this->getLanguageFallbackChainFactory(),
				$formatterFactory,
				new InMemoryDataTypeLookup(),
				new ItemIdParser(),
				$this->getFallbackLabelDescriptionLookupFactory()
			),
			$this->newUsageAccumulatorFactory(),
			$this->createMock( LanguageConverterFactory::class ),
			$this->createMock( LanguageFactory::class ),
			$allowDataAccessInUserLanguage
		);
		$factory->newRendererFromParser( $this->getParser( 'de', 'es' ) );
	}

	public function allowDataAccessInUserLanguageProvider(): array {
		return [
			[ true ],
			[ false ],
		];
	}

	private function getStatementGroupRendererFactory( bool $allowDataAccessInUserLanguage = false ): StatementGroupRendererFactory {
		$labelResolver = $this->createMock( PropertyLabelResolver::class );

		return new StatementGroupRendererFactory(
			$labelResolver,
			$this->getSnaksFinder(),
			$this->getEntityLookup(),
			new DataAccessSnakFormatterFactory(
				$this->getLanguageFallbackChainFactory(),
				$this->getSnakFormatterFactory(),
				new InMemoryDataTypeLookup(),
				new ItemIdParser(),
				$this->getFallbackLabelDescriptionLookupFactory()
			),
			$this->newUsageAccumulatorFactory(),
			MediaWikiServices::getInstance()->getLanguageConverterFactory(),
			MediaWikiServices::getInstance()->getLanguageFactory(),
			$allowDataAccessInUserLanguage
		);
	}

	private function newUsageAccumulatorFactory(): UsageAccumulatorFactory {
		return new UsageAccumulatorFactory(
			new EntityUsageFactory( new BasicEntityIdParser() ),
			new UsageDeduplicator( [] ),
			$this->createStub( EntityRedirectTargetLookup::class )
		);
	}

	private function getSnaksFinder(): SnaksFinder {
		$snakListFinder = $this->createMock( SnaksFinder::class );

		$snakListFinder->method( 'findSnaks' )
			->willReturnCallback( function(
				StatementListProvider $statementListProvider,
				NumericPropertyId $propertyId,
				array $acceptableRanks = null
			) {
				return [
					new PropertyValueSnak( $propertyId, new EntityIdValue( new ItemId( 'Q7' ) ) ),
				];
			} );

		return $snakListFinder;
	}

	private function getLanguageFallbackChainFactory(): LanguageFallbackChainFactory {
		return new LanguageFallbackChainFactory();
	}

	private function getSnakFormatterFactory(): OutputFormatSnakFormatterFactory {
		$snakFormatter = $this->createMock( SnakFormatter::class );

		$snakFormatter->method( 'formatSnak' )
			->willReturn( 'Kittens!' );

		$snakFormatterFactory = $this->createMock( OutputFormatSnakFormatterFactory::class );

		$snakFormatterFactory->method( 'getSnakFormatter' )
			->willReturn( $snakFormatter );

		return $snakFormatterFactory;
	}

	private function getEntityLookup(): EntityLookup {
		$entityLookup = $this->createMock( EntityLookup::class );

		$entityLookup->method( 'getEntity' )
			->willReturnCallback( function ( EntityId $id ) {
				return new Item( $id );
			} );

		$entityLookup->method( 'hasEntity' )
			->willReturn( true );

		return $entityLookup;
	}

	private function getParser(
		string $languageCode = 'en',
		string $userLanguageCode = 'es',
		bool $interfaceMessage = false,
		bool $disableContentConversion = false,
		bool $disableTitleConversion = false,
		int $outputType = Parser::OT_HTML
	): Parser {
		$parserOptions = $this->getParserOptions(
			$languageCode,
			$userLanguageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion
		);

		$parser = MediaWikiServices::getInstance()->getParserFactory()->create();

		$parser->setTitle( Title::makeTitle( NS_MAIN, 'Cat' ) );
		$parser->startExternalParse( null, $parserOptions, $outputType );

		return $parser;
	}

	private function getParserOptions( string $languageCode, string $userLanguageCode, bool $interfaceMessage,
		bool $disableContentConversion, bool $disableTitleConversion
	): ParserOptions {
		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		$language = $languageFactory->getLanguage( $languageCode );
		$userLanguage = $languageFactory->getLanguage( $userLanguageCode );

		$parserOptions = new ParserOptions( User::newFromId( 0 ), $userLanguage );
		$parserOptions->setTargetLanguage( $language );
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		return $parserOptions;
	}

	private function getFallbackLabelDescriptionLookupFactory(): FallbackLabelDescriptionLookupFactory {
		$languageFallbackLabelDescriptionLookupFactory = $this->createMock( FallbackLabelDescriptionLookupFactory::class );
		$languageFallbackLabelDescriptionLookupFactory->method( 'newLabelDescriptionLookup' )
			->willReturn( $this->createMock( FallbackLabelDescriptionLookup::class ) );

		return $languageFallbackLabelDescriptionLookupFactory;
	}

}
