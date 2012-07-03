<?php

namespace Wikibase\Test;
use Wikibase\ItemDiffView as ItemDiffView;
use Wikibase\Item as Item;
use Wikibase\ItemContent as ItemContent;

/**
 * Test for the Wikibase\ItemDiffView class.
 *
 * The tests are using "Database" to get its own set of temporal tables.
 * This is nice so we avoid poisoning an existing database.
 *
 * The tests are using "medium" so they are able to run alittle longer before they are killed.
 * Without this they will be killed after 1 second, but the setup of the tables takes so long
 * time that the first few tests get killed.
 *
 * The tests are doing some assumptions on the id numbers. If the database isn't empty when
 * when its filled with test items the ids will most likely get out of sync and the tests will
 * fail. It seems impossible to store the item ids back somehow and at the same time not being
 * dependant on some magically correct solution. That is we could use GetItemId but then we
 * would imply that this module in fact is correct.
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

	public function testConstructor() {
		new ItemDiffView( \RequestContext::getMain() );
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
		$diffView = new ItemDiffView( \RequestContext::getMain() );
		$this->assertInternalType(
			'string',
			$diffView->generateContentDiffBody(
				ItemContent::newFromItem( $item0 ),
				ItemContent::newFromItem( $item1 )
			)
		);
	}

}
