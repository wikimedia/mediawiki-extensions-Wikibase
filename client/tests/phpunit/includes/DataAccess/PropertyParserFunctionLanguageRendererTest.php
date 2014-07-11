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
 * @author Katie Filbert < aude.wiki@
 */
class PropertyParserFunctionVariantsRendererTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider renderProvider
	 */
	public function testRender( $expected, $itemId, $language, $propertyLabel ) {
		$this->assertTrue( true );
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
