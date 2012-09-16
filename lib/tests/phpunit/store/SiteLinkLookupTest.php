<?php

namespace Wikibase\Test;
use Wikibase\SiteLinkLookup as SiteLinkLookup;

/**
 * Tests for the Wikibase\SiteLinkLookup implementing classes.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SiteLinkLookupTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array();

		if ( defined( 'WB_VERSION' ) ) {
			$instances[] = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newSiteLinkLookup();
		}

		if ( defined( 'WBC_VERSION' ) ) {
			$instances[] = \Wikibase\ClientStoreFactory::getStore( 'sqlstore' )->newSiteLinkCache();
		}

		if ( empty( $instances ) ) {
			$this->markTestIncomplete( 'No sitelink lookup tables available' );
		}

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetConflictsForItem( SiteLinkLookup $lookup ) {
		$conflicts = $lookup->getConflictsForItem( \Wikibase\ItemObject::newEmpty() );
		$this->assertTrue( $conflicts === array() );
	}

}
