<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Item;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\ParserErrorMessageFormatter;
use Wikibase\Property;
use Wikibase\PropertyParserFunction;
use Wikibase\PropertyValueSnak;

/**
 * @covers Wikibase\PropertyParserFunction
 *
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunctionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider provideGetRenderer
	 */
	public function testGetRenderer( $languageCode, $outputType ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = new PropertyParserFunction( $parser, new ItemId( 'q42' ) );
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
		$instance = new PropertyParserFunction( $parser, new ItemId( 'q42' ) );
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
	 * @dataProvider provideProcessRenderedText
	 */
	public function testProcessRenderedText( $outputType, $text, $expected ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = new PropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->processRenderedText( $text ) );
	}

	public function provideProcessRenderedText() {
		return array(
			array( \Parser::OT_HTML, 'fo<b>ob</b>ar', 'fo&#60;b&#62;ob&#60;/b&#62;ar' ),
			array( \Parser::OT_WIKI, 'fo<b>ob</b>ar', 'fo<b>ob</b>ar' ),
			array( \Parser::OT_PREPROCESS, 'fo<b>ob</b>ar', 'fo&#60;b&#62;ob&#60;/b&#62;ar' ),
			array( \Parser::OT_PLAIN, 'fo<b>ob</b>ar', 'fo<b>ob</b>ar' ),
		);
	}

	/**
	 * @dataProvider provideProcessRenderedArray
	 */
	public function testProcessRenderedArray( $outputType, $textArray, $expected ) {
		$parser = new \Parser();
		$parserOptions = new \ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = new PropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->processRenderedArray( $textArray ) );
	}

	public function provideProcessRenderedArray() {
		return array(
			array( \Parser::OT_HTML, array(
				'zh-cn' => 'fo<b>ob</b>ar',
				'zh-tw' => 'FO<b>OB</b>AR',
			), '-{zh-cn:fo&#60;b&#62;ob&#60;/b&#62;ar;zh-tw:FO&#60;b&#62;OB&#60;/b&#62;AR;}-' ),
			array( \Parser::OT_WIKI, array(
				'zh-cn' => 'fo<b>ob</b>ar',
				'zh-tw' => 'FO<b>OB</b>AR',
			), '-{zh-cn:fo<b>ob</b>ar;zh-tw:FO<b>OB</b>AR;}-' ),
			array( \Parser::OT_PREPROCESS, array(
				'zh-cn' => 'fo<b>ob</b>ar',
				'zh-tw' => 'FO<b>OB</b>AR',
			), '-{zh-cn:fo&#60;b&#62;ob&#60;/b&#62;ar;zh-tw:FO&#60;b&#62;OB&#60;/b&#62;AR;}-' ),
			array( \Parser::OT_PLAIN, array(
				'zh-cn' => 'fo<b>ob</b>ar',
				'zh-tw' => 'FO<b>OB</b>AR',
			), '-{zh-cn:fo<b>ob</b>ar;zh-tw:FO<b>OB</b>AR;}-' ),
		);
	}

}
