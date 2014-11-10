<?php

namespace Wikibase\DataAccess\Tests;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRendererFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\PropertyClaimsRendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyClaimsRendererFactoryTest extends \PHPUnit_Framework_TestCase {

	public function testNewRendererForInterfaceMessage() {
		$parser = $this->getParser( 'zh', true, false, false, Parser::OT_HTML );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer',
			$renderer
		);
	}

	public function testNewRenderer_contentConversionDisabled() {
		$parser = $this->getParser( 'zh', false, true, false, Parser::OT_HTML );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer',
			$renderer
		);
	}

	public function testNewRenderer_titleConversionDisabled() {
		$parser = $this->getParser( 'zh', false, false, true, Parser::OT_HTML );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\VariantsAwareRenderer',
			$renderer
		);
	}

	/**
	 * @dataProvider newRenderer_forParserFormatProvider
	 */
	public function testNewRenderer_forParserFormat( $languageCode, $format ) {
		$parser = $this->getParser( $languageCode, false, false, false, $format );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer',
			$renderer
		);
	}

	public function newRenderer_forParserFormatProvider() {
		return array(
			array( 'ku', Parser::OT_PLAIN ),
			array( 'zh', Parser::OT_WIKI ),
			array( 'zh', Parser::OT_PREPROCESS )
		);
	}

	public function testNewRenderer_forNonVariantLanguage() {
		$parser = $this->getParser( 'en', true, false, false, Parser::OT_HTML );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer',
			$renderer
		);
	}

	public function testNewRender_forVariantLanguage() {
		$parser = $this->getParser( 'zh', false, false, false, Parser::OT_HTML );

		$rendererFactory = $this->getPropertyClaimsRendererFactory();
		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf(
			'Wikibase\DataAccess\PropertyParserFunction\VariantsAwareRenderer',
			$renderer
		);
	}

	private function getPropertyClaimsRendererFactory() {
		return new PropertyClaimsRendererFactory(
			$this->getPropertyIdResolver(),
			$this->getSnaksFinder(),
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory(),
			$this->getValueFormatterBuilders()
		);
	}

	private function getPropertyIdResolver() {
		$propertyIdResolver = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyIdResolver'
			)
			->disableOriginalConstructor()
			->getMock();

		return $propertyIdResolver;
	}

	private function getSnaksFinder() {
		$snakListFinder = $this->getMockBuilder(
				'Wikibase\DataAccess\PropertyParserFunction\SnaksFinder'
			)
			->disableOriginalConstructor()
			->getMock();

		return $snakListFinder;
	}

	private function getLanguageFallbackChainFactory() {
		$languageFallbackChainFactory = $this->getMockBuilder(
				'Wikibase\LanguageFallbackChainFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		return $languageFallbackChainFactory;
	}

	private function getSnakFormatterFactory() {
		$snakFormatter = $this->getMockBuilder( 'Wikibase\Lib\SnakFormatter' )
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory = $this->getMockBuilder(
				'Wikibase\Lib\OutputFormatSnakFormatterFactory'
			)
			->disableOriginalConstructor()
			->getMock();

		$snakFormatterFactory->expects( $this->any() )
			->method( 'getSnakFormatter' )
			->will( $this->returnValue( $snakFormatter ) );

		return $snakFormatterFactory;
	}

	private function getValueFormatterBuilders() {
		return $this->getMockBuilder( 'Wikibase\Lib\WikibaseValueFormatterBuilders' )
			->disableOriginalConstructor()
			->getMock();
	}

	private function getParser( $languageCode, $interfaceMessage, $disableContentConversion,
		$disableTitleConversion, $outputType
	) {
		$parserConfig = array( 'class' => 'Parser' );

		$parserOptions = $this->getParserOptions(
			$languageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion
		);

		$parser = new Parser( $parserConfig );

		$parser->setTitle( Title::newFromText( 'Cat' ) );
		$parser->startExternalParse( null, $parserOptions, $outputType );

		return $parser;
	}

	private function getParserOptions( $languageCode, $interfaceMessage, $disableContentConversion,
		$disableTitleConversion
	) {
		$language = Language::factory( $languageCode );

		$parserOptions = new ParserOptions( User::newFromId( 0 ), $languageCode );
		$parserOptions->setTargetLanguage( $language );
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		return $parserOptions;
	}

}
