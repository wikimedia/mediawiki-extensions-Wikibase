<?php

namespace Wikibase\Test;
use Wikibase\ItemContentDiffView;
use Wikibase\Item;
use Wikibase\ItemContent;

/**
 * Test for the Wikibase\ItemDiffView class.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
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
		$this->assertTrue( true );
	}

	public function itemProvider() {
		$item = \Wikibase\Item::newEmpty();
		$item->setDescription( 'en', 'ohi there' );
		$item->setLabel( 'de', 'o_O' );
		$item->addAliases( 'nl', array( 'foo', 'bar' ) );

		$itemClone = $item->copy();
		$itemClone->setAliases( 'nl', array( 'daaaah' ) );
		$itemClone->setLabel( 'en', 'O_o' );
		$itemClone->removeDescription( 'en' );

		return array(
			array( \Wikibase\Item::newEmpty(), \Wikibase\Item::newEmpty() ),
			array( \Wikibase\Item::newEmpty(), $item ),
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
