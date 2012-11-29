<?php

namespace Wikibase\Test;
use \Wikibase\ItemDeletionUpdate;
use \Wikibase\ItemContent;

/**
 * Tests for the Wikibase\ItemDeletionUpdate class.
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
 * @group DataUpdate
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemDeletionUpdateTest extends \MediaWikiTestCase {
	//@todo: make this a baseclass to use with all types of entities.

	public function testConstruct() {
		$update = new ItemDeletionUpdate( \Wikibase\ItemContent::newEmpty() );
		$this->assertInstanceOf( '\Wikibase\ItemDeletionUpdate', $update );
		$this->assertInstanceOf( '\Wikibase\EntityDeletionUpdate', $update );
		$this->assertInstanceOf( '\DataUpdate', $update );
	}

	public function itemProvider() {
		return array_map(
			function( ItemContent $itemContent ) { return array( $itemContent ); },
			\Wikibase\Test\TestItemContents::getItems()
		);
	}

	/**
	 * @dataProvider itemProvider
	 * @param ItemContent $itemContent
	 */
	public function testDoUpdate( ItemContent $itemContent ) {
		$itemContent->save( '', null, EDIT_NEW );
		$update = new ItemDeletionUpdate( $itemContent );
		$update->doUpdate();

		$id = $itemContent->getItem()->getId()->getNumericId();

		$linkLookup = \Wikibase\StoreFactory::getStore()->newSiteLinkCache();
		$this->assertEquals( 0, $linkLookup->countLinks( array( $id ) ) );
	}

}
