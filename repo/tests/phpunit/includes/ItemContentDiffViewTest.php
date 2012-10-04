<?php

namespace Wikibase\Test;
use Wikibase\ItemContentDiffView as ItemContentDiffView;
use Wikibase\Item as Item;
use Wikibase\ItemContent as ItemContent;

/**
 * Test for the Wikibase\ItemDiffView class.
 *
 * @file
 * @since 0.1
 *
 * @ingroup Wikibase
 * @ingroup Test
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
		new ItemContentDiffView( \RequestContext::getMain() );
	}

	public function itemProvider() {
		$item = \Wikibase\ItemObject::newEmpty();
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->addAliases( 'nl', array( 'foo', 'bar' ) );

		$itemClone = $item->copy();
		$itemClone->setAliases( 'nl', array( 'daaaah' ) );
		$itemClone->setLabel( 'en', 'O_o' );
		$itemClone->removeDescription( 'en' );

		return array(
			array( \Wikibase\ItemObject::newEmpty(), \Wikibase\ItemObject::newEmpty() ),
			array( \Wikibase\ItemObject::newEmpty(), $item ),
			array( $item, $itemClone ),
		);
	}

	/**
	 * @dataProvider itemProvider
	 */
	public function testGenerateContentDiffBody( Item $item0, Item $item1 ) {
		$diffView = new ItemContentDiffView( \RequestContext::getMain() );
		$this->assertInternalType(
			'string',
			$diffView->generateContentDiffBody(
				ItemContent::newFromItem( $item0 ),
				ItemContent::newFromItem( $item1 )
			)
		);
	}

}
