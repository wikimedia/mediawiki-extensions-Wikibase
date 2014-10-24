<?php

namespace Wikibase\DataAccess\Tests;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunction\RendererFactory;
use Wikibase\Client\Usage\HashUsageAccumulator;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\RendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class RendererFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider newRendererFromParserProvider
	 */
	public function testNewRendererFromParser( $expected, $languageCode, $interfaceMessage,
		$disableContentConversion, $disableTitleConversion, $outputType
	) {
		$parser = $this->getParser(
			$languageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion,
			$outputType
		);

		$rendererFactory = new RendererFactory(
			$this->getSnaksFinder(),
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory()
		);

		$renderer = $rendererFactory->newRendererFromParser( $parser );

		$this->assertInstanceOf( $expected, $renderer );
	}

	public function newRendererFromParserProvider() {
		$languageRendererClass = 'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer';
		$variantsRendererClass = 'Wikibase\DataAccess\PropertyParserFunction\VariantsAwareRenderer';

		return array(
			array( $languageRendererClass, 'en', false, false, false, Parser::OT_HTML ),
			array( $languageRendererClass, 'ku', false, false, false, Parser::OT_PLAIN ),
			array( $languageRendererClass, 'zh', false, false, false, Parser::OT_WIKI ),
			array( $languageRendererClass, 'zh', false, true, false, Parser::OT_HTML ),
			array( $languageRendererClass, 'zh', true, false, false, Parser::OT_HTML ),
			array( $languageRendererClass, 'zh', false, false, false, Parser::OT_PREPROCESS ),
			array( $variantsRendererClass, 'zh', false, false, true, Parser::OT_HTML ),
			array( $variantsRendererClass, 'ku', false, false, false, Parser::OT_HTML ),
		);
	}

	public function testNewLanguageAwareRenderer() {
		$rendererFactory = new RendererFactory(
			$this->getSnaksFinder(),
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory()
		);

		$language = Language::factory( 'he' );
		$usageAcc = new HashUsageAccumulator();
		$renderer = $rendererFactory->newLanguageAwareRenderer( $language, $usageAcc );

		$languageRendererClass = 'Wikibase\DataAccess\PropertyParserFunction\LanguageAwareRenderer';
		$this->assertInstanceOf( $languageRendererClass, $renderer );
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
