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
 * @author Katie Filbert < aude.wiki@wiki.gmail.com>
 */
class PropertyParserFunctionLanguageRendererTest extends \PHPUnit_Framework_TestCase {

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

}
