<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\PropertyParserFunction;

/**
 * @covers Wikibase\PropertyParserFunction
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunctionTest extends \PHPUnit_Framework_TestCase {

	public function getPropertyParserFunction( $parser, $entityId ) {
		$entityLookup = new MockRepository();
		$propertyLabelResolver = new MockPropertyLabelResolver( $parser->getTargetLanguage(), $entityLookup );

		return new PropertyParserFunction( $parser, $entityId, $entityLookup,
			$propertyLabelResolver );
	}

	/**
	 * @dataProvider provideGetRenderer
	 */
	public function testGetRenderer( $languageCode, $outputType ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$renderer = $instance->getRenderer( \Language::factory( $languageCode ) );
		$this->assertInstanceOf( 'Wikibase\PropertyParserFunctionRenderer', $renderer );
	}

	public function provideGetRenderer() {
		return array(
			array( 'en', \Parser::OT_HTML ),
			array( 'zh', \Parser::OT_WIKI ),
		);
	}

	/**
	 * @dataProvider provideIsParserUsingVariants
	 */
	public function testIsParserUsingVariants(
		$outputType, $interfaceMessage, $disableContentConversion, $disableTitleConversion, $expected
	) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->isParserUsingVariants() );
	}

	public function provideIsParserUsingVariants() {
		return array(
			array( \Parser::OT_HTML, false, false, false, true ),
			array( \Parser::OT_WIKI, false, false, false, false ),
			array( \Parser::OT_PREPROCESS, false, false, false, false ),
			array( \Parser::OT_PLAIN, false, false, false, false ),
			array( \Parser::OT_HTML, true, false, false, false ),
			array( \Parser::OT_HTML, false, true, false, false ),
			array( \Parser::OT_HTML, false, false, true, true ),
		);
	}

	/**
	 * @dataProvider provideProcessRenderedArray
	 */
	public function testProcessRenderedArray( $outputType, $textArray, $expected ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->processRenderedArray( $textArray ) );
	}

	public function provideProcessRenderedArray() {
		return array(
			array( \Parser::OT_HTML, array(
				'zh-cn' => 'fo&#60;ob&#62;ar',
				'zh-tw' => 'FO&#60;OB&#62;AR',
			), '-{zh-cn:fo&#60;ob&#62;ar;zh-tw:FO&#60;OB&#62;AR;}-' ),
			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			array( \Parser::OT_HTML, array(), '' ),
		);
	}

}
