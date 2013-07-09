<?php

namespace Wikibase\Test;
use Language;
use Site;
use TestSites;
use Wikibase\DirectSqlStore;
use Wikibase\Settings;

/**
 * Tests for the Wikibase\DirectSqlStore class.
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
 * @since 0.4
 *
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @covers Wikibase\DirectSqlStore
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @licence GNU GPL v2+
 * @author DanielKinzler
 */
class DirectSqlStoreTest extends \MediaWikiTestCase {

	protected function newStore() {
		$site = new Site( \MediaWikiSite::TYPE_MEDIAWIKI );
		$site->setGlobalId( 'dummy' );
		$lang = Language::factory( 'en' );

		$store = new DirectSqlStore( $lang, 'DirectStoreSqlTestDummyRepoId');
		$store->setSite( $site ); //TODO: inject via constructor once that is possible

		return $store;
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType ) {
		$store = $this->newStore();

		$obj = $store->$getter();

		$this->assertInstanceOf( $expectedType, $obj );
	}

	public static function provideGetters() {
		return array(
			array( 'getEntityUsageIndex', 'Wikibase\EntityUsageIndex' ),
			array( 'getSiteLinkTable', 'Wikibase\SiteLinkTable' ),
			array( 'getEntityLookup', 'Wikibase\EntityLookup' ),
			array( 'getTermIndex', 'Wikibase\TermIndex' ),
			array( 'getPropertyLabelResolver', 'Wikibase\PropertyLabelResolver' ),
			array( 'newChangesTable', 'Wikibase\ChangesTable' ),
			array( 'getPropertyInfoStore', 'Wikibase\PropertyInfoStore' ),
		);
	}

}
