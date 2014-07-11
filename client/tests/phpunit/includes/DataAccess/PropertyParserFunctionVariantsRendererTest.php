<?php

namespace Wikibase\Test;

use Language;
use Parser;
use ParserOptions;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataAccess\PropertyParserFunctionVariantsRenderer;

/**
 * @covers Wikibase\DataAccess\PropertyParserFunctionVariantsRenderer
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class PropertyParserFunctionVariantsRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider renderProvider
	 */
	public function testRender( $expected, $itemId, $language, $propertyLabel ) {
		$languageRenderer = $this->getMockBuilder(
			'Wikibase\DataAccess\PropertyParserFunctionLanguageRenderer'
		)
		->disableOriginalConstructor()
		->getMock();

		$languageRenderer->expects( $this->any() )
			->method( 'render' )
			->will( $this->returnValue( 'mooooo' ) );

		$variantsRenderer = new PropertyParserFunctionVariantsRenderer( $languageRenderer );

		$result = $variantsRenderer->render( $itemId, $language, $propertyLabel );

		$this->assertEquals( $expected, $result );
	}

	public function renderProvider() {
		return array(
			array(
				'-{zh:mooooo;zh-hans:mooooo;zh-hant:mooooo;zh-cn:mooooo;zh-hk:mooooo;zh-mo:mooooo;zh-my:mooooo;zh-sg:mooooo;zh-tw:mooooo;}-',
				new ItemId( 'Q3' ),
				Language::factory( 'zh' ),
				'cat'
			)
		);
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
/*
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
*/
}
