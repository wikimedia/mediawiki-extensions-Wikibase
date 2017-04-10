<?php

namespace Wikibase\Client\Tests\DataAccess\PropertyParserFunction;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\PropertyParserFunction\LanguageAwareRenderer;
use Wikibase\Client\DataAccess\PropertyParserFunction\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\PropertyParserFunction\VariantsAwareRenderer;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Client\DataAccess\PropertyParserFunction\StatementGroupRendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementGroupRendererFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider wikitextTypeProvider
	 */
	public function testNewRendererFromParser_forWikitextType( $type ) {
		$parser = $this->getParser( 'zh', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser, $type );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function wikitextTypeProvider() {
		return [
			[ 'escaped-plaintext' ],
			[ 'rich-wikitext' ],
		];
	}

	public function testNewRenderer_contentConversionDisabled() {
		$parser = $this->getParser( 'zh', 'es', false, true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function testNewRenderer_titleConversionDisabled() {
		$parser = $this->getParser( 'zh', 'es', false, false, true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( VariantsAwareRenderer::class, $renderer );
	}

	/**
	 * @dataProvider newRenderer_forParserFormatProvider
	 */
	public function testNewRenderer_forParserFormat( $languageCode, $format ) {
		$parser = $this->getParser( $languageCode, 'es', false, false, false, $format );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function newRenderer_forParserFormatProvider() {
		return [
			[ 'ku', Parser::OT_PLAIN ],
			[ 'zh', Parser::OT_WIKI ],
			[ 'zh', Parser::OT_PREPROCESS ]
		];
	}

	public function testNewRenderer_forNonVariantLanguage() {
		$parser = $this->getParser( 'en', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( LanguageAwareRenderer::class, $renderer );
	}

	public function testNewRender_forVariantLanguage() {
		$parser = $this->getParser( 'zh' );

		$rendererFactory = $this->getStatementGroupRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( VariantsAwareRenderer::class, $renderer );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testNewRenderer_usageTracking( $allowDataAccessInUserLanguage ) {
		$parser = $this->getParser( 'en', 'es', true );

		$rendererFactory = $this->getStatementGroupRendererFactory( $allowDataAccessInUserLanguage );
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$usageAccumulator = new ParserOutputUsageAccumulator( $parser->getOutput() );
		$this->assertEquals( "Kittens!", $renderer->render( new ItemId( 'Q1' ), 'P1' ) );

		$usages = $usageAccumulator->getUsages();
		if ( $allowDataAccessInUserLanguage ) {
			$this->assertArrayHasKey( 'Q7#L.es', $usages );
		} else {
			$this->assertArrayHasKey( 'Q7#L.en', $usages );
		}
		$this->assertArrayHasKey( 'Q7#T', $usages );
	}

	/**
	 * @dataProvider allowDataAccessInUserLanguageProvider
	 */
	public function testNewRendererFromParser_languageOption( $allowDataAccessInUserLanguage ) {
		$idResolver = $this->getMockBuilder( PropertyIdResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$formatterFactory = $this->getMockBuilder( OutputFormatSnakFormatterFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$formatterFactory->expects( $this->once() )
			->method( 'getSnakFormatter' )
			->will( $this->returnCallback(
				function( $format, FormatterOptions $options ) use ( $allowDataAccessInUserLanguage )  {
					$this->assertSame(
						$allowDataAccessInUserLanguage ? 'es' : 'de',
						$options->getOption( ValueFormatter::OPT_LANG )
					);
					return $this->getMock( SnakFormatter::class );
				}
			) );

		$factory = new StatementGroupRendererFactory(
			$idResolver,
			new SnaksFinder(),
			$this->getMock( EntityLookup::class ),
			new DataAccessSnakFormatterFactory(
				$this->getLanguageFallbackChainFactory(),
				$formatterFactory,
				$this->getMock( PropertyDataTypeLookup::class )
			),
			$allowDataAccessInUserLanguage
		);
		$factory->newRendererFromParser( $this->getParser( 'de', 'es' ) );
	}

	public function allowDataAccessInUserLanguageProvider() {
		return [
			[ true ],
			[ false ],
		];
	}

	private function getStatementGroupRendererFactory( $allowDataAccessInUserLanguage = false ) {
		return new StatementGroupRendererFactory(
			$this->getPropertyIdResolver(),
			$this->getSnaksFinder(),
			$this->getEntityLookup(),
			new DataAccessSnakFormatterFactory(
				$this->getLanguageFallbackChainFactory(),
				$this->getSnakFormatterFactory(),
				$this->getMock( PropertyDataTypeLookup::class )
			),
			$allowDataAccessInUserLanguage
		);
	}

	/**
	 * @return PropertyIdResolver
	 */
	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->getMockBuilder( PropertyIdResolver::class )
			->disableOriginalConstructor()
			->getMock();

		$propertyIdResolver->expects( $this->any() )
			->method( 'resolvePropertyId' )
			->will( $this->returnCallback( function ( $name, $lang ) {
				return new PropertyId( $name );
			} ) );

		return $propertyIdResolver;
	}

	/**
	 * @return SnaksFinder
	 */
	private function getSnaksFinder() {
		$snakListFinder = $this->getMock( SnaksFinder::class );

		$snakListFinder->expects( $this->any() )
			->method( 'findSnaks' )
			->will( $this->returnCallback( function(
				StatementListProvider $statementListProvider,
				PropertyId $propertyId,
				array $acceptableRanks = null
			) {
				return [
					new PropertyValueSnak( $propertyId, new EntityIdValue( new ItemId( 'Q7' ) ) )
				];
			} ) );

		return $snakListFinder;
	}

	private function getLanguageFallbackChainFactory() {
		return new LanguageFallbackChainFactory();
	}

	/**
	 * @return OutputFormatSnakFormatterFactory
	 */
	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMock( SnakFormatter::class );

		$snakFormatter->expects( $this->any() )
			->method( 'formatSnak' )
			->will( $this->returnValue( 'Kittens!' ) );

		$snakFormatterFactory = $this->getMockBuilder( OutputFormatSnakFormatterFactory::class )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		$entityLookup = $this->getMock( EntityLookup::class );

		$entityLookup->expects( $this->any() )
			->method( 'getEntity' )
			->will( $this->returnCallback( function ( EntityId $id ) {
				return new Item( $id );
			} ) );

		return $entityLookup;
	}

	private function getParser(
		$languageCode = 'en',
		$userLanguageCode = 'es',
		$interfaceMessage = false,
		$disableContentConversion = false,
		$disableTitleConversion = false,
		$outputType = Parser::OT_HTML
	) {
		$parserOptions = $this->getParserOptions(
			$languageCode,
			$userLanguageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion
		);

		$parser = new Parser( [ 'class' => 'Parser' ] );

		$parser->setTitle( Title::newFromText( 'Cat' ) );
		$parser->startExternalParse( null, $parserOptions, $outputType );

		return $parser;
	}

	private function getParserOptions( $languageCode, $userLanguageCode, $interfaceMessage,
		$disableContentConversion, $disableTitleConversion
	) {
		$language = Language::factory( $languageCode );
		$userLanguage = Language::factory( $userLanguageCode );

		$parserOptions = new ParserOptions( User::newFromId( 0 ), $userLanguage );
		$parserOptions->setTargetLanguage( $language );
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		return $parserOptions;
	}

}
