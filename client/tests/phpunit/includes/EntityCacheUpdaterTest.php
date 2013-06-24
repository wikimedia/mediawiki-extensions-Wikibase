<?php

namespace Wikibase\Test;
use Wikibase\EntityCacheTable;
use Wikibase\EntityCacheUpdater;
use Wikibase\EntityChange;
use Wikibase\Item;
use Wikibase\Settings;

/**
 * Tests for the Wikibase\EntityCacheUpdater class.
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
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseEntityCache
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityCacheUpdaterTest extends \MediaWikiTestCase {

	public function testConstructor() {
		new EntityCacheUpdater( new EntityCacheTable() );
		$this->assertTrue( true );
	}

	public function handleChangeProvider() {
		$argLists = array();

		$sourceItem = Item::newEmpty();
		$sourceItem->setId( 42 );
		$targetItem = clone $sourceItem;
		$targetItem->setLabel( 'en', 'ohi there' );
		$change = EntityChange::newFromUpdate( 'update', $sourceItem, $targetItem );

		$argLists[] = array( $change, $sourceItem, $targetItem );

		return $argLists;
	}

	/**
	 * Data provider refuses to work for some reason o_O
	 */
	public function testHandleChange( /* EntityChange $change, Item $sourceItem, Item $targetItem */ ) {
		if ( Settings::get( 'repoDatabase' ) !== null ) { //NOTE: repoDatabase == false means it's local
			$this->markTestSkipped( "Can't test EntityCacheUpdater if local caching is not configured."
				. "\nThe repoDatabase setting instructs WikibaseClient to access the repo database directly.");
		}

		foreach ( $this->handleChangeProvider() as $argList ) {
			list( $change, , ) = $argList;

			$cacheUpdater = new EntityCacheUpdater( new EntityCacheTable() );

			$cacheUpdater->handleChange( $change );

			// TODO: test if the result matches expected behavior
			$this->assertTrue( true );
		}
	}

}
	
