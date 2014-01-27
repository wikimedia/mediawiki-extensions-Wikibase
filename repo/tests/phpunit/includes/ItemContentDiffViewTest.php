<?php

namespace Wikibase\Test;

use RequestContext;
use Wikibase\ItemContentDiffView;
use Wikibase\Item;
use Wikibase\ItemContent;

/**
 * @covers Wikibase\ItemContentDiffView
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDiffViewTest extends \MediaWikiTestCase {

	//@todo: make this a baseclass to use with all types of entities.

	public function testConstructor() {
		new ItemContentDiffView( RequestContext::getMain() );
		$this->assertTrue( true );
	}

	public function itemProvider() {
		$item = Item::newEmpty();
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->addAliases( 'nl', array( 'foo', 'bar' ) );

		$itemClone = $item->copy();
		$itemClone->setAliases( 'nl', array( 'daaaah' ) );
		$itemClone->setLabel( 'en', 'O_o' );
		$itemClone->removeDescription( 'en' );

		return array(
			array( Item::newEmpty(), Item::newEmpty() ),
			array( Item::newEmpty(), $item ),
			array( $item, $itemClone ),
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGenerateContentDiffBody( Item $item0, Item $item1 ) {
		$diffView = new ItemContentDiffView( RequestContext::getMain() );
		$this->assertInternalType(
			'string',
			$diffView->generateContentDiffBody(
				ItemContent::newFromItem( $item0 ),
				ItemContent::newFromItem( $item1 )
			)
		);
	}

}
