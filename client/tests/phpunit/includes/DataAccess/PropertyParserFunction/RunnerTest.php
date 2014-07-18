<?php

namespace Wikibase\DataAccess\Tests\PropertyParserFunction;

use Language;
use Parser;
use ParserOptions;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Test\MockPropertyLabelResolver;
use Wikibase\Test\MockRepository;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunction\Runner
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseDataAccess
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class PropertyParserFunctionRunnerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param Parser $parser
	 * @param Entity|null $entity
	 *
	 * @return PropertyParserFunctionRunner
	 */
	private function getRunner( Parser $parser, Entity $entity = null ) {
		$entityLookup = new MockRepository();

		if ( $entity !== null ) {
			$entityLookup->putEntity( $entity );
		}

		$propertyLabelResolver = new MockPropertyLabelResolver(
			$parser->getTargetLanguage(),
			$entityLookup
		);

		return new Runner(
			$entityLookup,
			$propertyLabelResolver,
			$this->getSiteLinkLookup(),
			'enwiki'
		);
	}

	/**
	 * @dataProvider getRendererProvider
	 */
	public function testGetRenderer( $languageCode, $outputType ) {
		$parser = new Parser();
		$parserOptions = new ParserOptions();
		$parser->startExternalParse( null, $parserOptions, $outputType );
		$runner = $this->getRunner( $parser );
		$renderer = $runner->getRenderer( Language::factory( $languageCode ) );
		$this->assertInstanceOf( 'Wikibase\DataAccess\PropertyParserFunction\Renderer', $renderer );
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
		$parserOptions = new ParserOptions();
		$parserOptions->setInterfaceMessage( $interfaceMessage );
		$parserOptions->disableContentConversion( $disableContentConversion );
		$parserOptions->disableTitleConversion( $disableTitleConversion );

		$parser = new Parser();
		$parser->startExternalParse( null, $parserOptions, $outputType );

		$runner = $this->getRunner( $parser );

		$this->assertEquals( $expected, $runner->isParserUsingVariants( $parser ) );
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
		$runner = $this->getRunner( $parser );
		$this->assertEquals( $expected, $runner->processRenderedArray( $textArray ) );
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

		$runner = $this->getRunner( $parser, $item );
		$lang = Language::factory( 'qqx' );

		$result = $runner->renderInLanguage( $item->getId(), 'invalidLabel', $lang );

		// Test against the regexp of the {{#iferror parser function, as that should be able
		// to detect errors from PropertyParserFunctionRunner. See ExtParserFunctions::iferror
		$this->assertRegExp(
			'/<(?:strong|span|p|div)\s(?:[^\s>]*\s+)*?class="(?:[^"\s>]*\s+)*?error(?:\s[^">]*)?"/',
			$result
		);

		$this->assertRegExp(
			'/wikibase-property-render-error.*invalidLabel.*qqx/',
			$result
		);
	}

	private function getSiteLinkLookup() {
		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkLookup' )
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( new ItemId( 'Q3' ) ) );

		return $siteLinkLookup;
	}

}
