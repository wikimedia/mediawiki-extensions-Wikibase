<?php

namespace Wikibase\DataAccess\Tests;

use Language;
use Parser;
use ParserOptions;
use Title;
use User;
use Wikibase\DataAccess\PropertyParserFunctionRendererFactory;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunctionRendererFactory
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionRendererFactoryTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider newFromParserProvider
	 */
	public function testNewFromParser( $expected, $languageCode, $interfaceMessage,
		$disableContentConversion, $disableTitleConversion, $outputType
	) {
		$parser = $this->getParser(
			$languageCode,
			$interfaceMessage,
			$disableContentConversion,
			$disableTitleConversion,
			$outputType
		);

		$rendererFactory = new PropertyParserFunctionRendererFactory(
			$this->getEntityLookup(),
			$this->getPropertyLabelResolver(),
			Language::factory( $languageCode )
		);

		$renderer = $rendererFactory->newFromParser( $parser );

		$this->assertInstanceOf( $expected, $renderer );
	}

	public function newFromParserProvider() {
		$languageRendererClass = 'Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer';
		$variantsRendererClass = 'Wikibase\DataAccess\PropertyParserFunctionVariantsRenderer';

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

	private function getEntityLookup() {
		$entityLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\EntityLookup' )
			->disableOriginalConstructor()
			->getMock();

		return $entityLookup;
	}

	private function getPropertyLabelResolver() {
		$propertyLabelResolver = $this->getMockBuilder( 'Wikibase\PropertyLabelResolver' )
			->disableOriginalConstructor()
			->getMock();

		return $propertyLabelResolver;
	}

}
