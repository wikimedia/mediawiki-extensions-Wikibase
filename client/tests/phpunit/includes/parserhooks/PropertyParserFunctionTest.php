<?php

namespace Wikibase\Test;

use Language;
use Parser;
use ParserOptions;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
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
 * @author Marius Hoch < hoo@online.de >
 */
class PropertyParserFunctionTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param Parser $parser
	 * @param EntityId $entityId
	 * @param Entity|null $entity
	 *
	 * @return PropertyParserFunction
	 */
	private function getPropertyParserFunction(
		Parser $parser,
		EntityId $entityId,
		Entity $entity = null
	) {
		$entityLookup = new MockRepository();

		if ( $entity !== null ) {
			$entityLookup->putEntity( $entity );
		}

		$propertyLabelResolver = new MockPropertyLabelResolver(
			$parser->getTargetLanguage(),
			$entityLookup
		);

		return new PropertyParserFunction( $parser, $entityId, $entityLookup,
			$propertyLabelResolver );
	}

	/**
	 * @dataProvider getRendererProvider
	 */
	public function testGetRenderer( $languageCode, $outputType ) {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$renderer = $instance->getRenderer( Language::factory( $languageCode ) );
		$this->assertInstanceOf( 'Wikibase\PropertyParserFunctionRenderer', $renderer );
	}

	public function getRendererProvider() {
		return array(
			array( 'en', Parser::OT_HTML ),
			array( 'zh', Parser::OT_WIKI ),
		);
	}

	/**
	 * @dataProvider isParserUsingVariantsProvider
	 */
	public function testIsParserUsingVariants(
		$outputType,
		$interfaceMessage,
		$disableContentConversion,
		$disableTitleConversion,
		$expected
	) {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->isParserUsingVariants() );
	}

	public function isParserUsingVariantsProvider() {
		return array(
			array( Parser::OT_HTML, false, false, false, true ),
			array( Parser::OT_WIKI, false, false, false, false ),
			array( Parser::OT_PREPROCESS, false, false, false, false ),
			array( Parser::OT_PLAIN, false, false, false, false ),
			array( Parser::OT_HTML, true, false, false, false ),
			array( Parser::OT_HTML, false, true, false, false ),
			array( Parser::OT_HTML, false, false, true, true ),
		);
	}

	/**
	 * @dataProvider processRenderedArrayProvider
	 */
	public function testProcessRenderedArray( $outputType, array $textArray, $expected ) {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$instance = $this->getPropertyParserFunction( $parser, new ItemId( 'q42' ) );
		$this->assertEquals( $expected, $instance->processRenderedArray( $textArray ) );
	}

	public function processRenderedArrayProvider() {
		return array(
			array( Parser::OT_HTML, array(
				'zh-cn' => 'fo&#60;ob&#62;ar',
				'zh-tw' => 'FO&#60;OB&#62;AR',
			), '-{zh-cn:fo&#60;ob&#62;ar;zh-tw:FO&#60;OB&#62;AR;}-' ),
			// Don't create "-{}-" for empty input,
			// to keep the ability to check a missing property with {{#if: }}.
			array( Parser::OT_HTML, array(), '' ),
		);
	}

	public function testRenderInLanguageError() {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( null, $parserOptions, Parser::OT_WIKI );

		$item = Item::newEmpty();
		$item->setId( new ItemId( 'Q42' ) );

		$instance = $this->getPropertyParserFunction( $parser, $item->getId(), $item );
		$lang = Language::factory( 'qqx' );

		$result = $instance->renderInLanguage( 'invalidLabel', $lang );

		// Test against the regexp of the {{#iferror parser function, as that should be able
		// to detect errors from PropertyParserFunction. See ExtParserFunctions::iferror
		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

}
