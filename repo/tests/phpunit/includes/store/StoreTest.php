<?php

namespace Wikibase\Test;
use Wikibase\Store;

/**
 * Tests for the Wikibase\Store implementing classes.
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
 * @group WikibaseStore
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
class StoreTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( new \Wikibase\SqlStore( 'testing', 1, CACHE_NONE ) );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testRebuild( Store $store ) {
		$store->rebuild();
		$this->assertTrue( true );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewSiteLinkCache( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\SiteLinkLookup', $store->newSiteLinkCache() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewTermCache( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\TermIndex', $store->getTermIndex() );
	}

	/**
	 * @dataProvider instanceProvider
	 * @param Store $store
	 */
	public function testNewIdGenerator( Store $store ) {
		$this->assertInstanceOf( '\Wikibase\IdGenerator', $store->newIdGenerator() );
	}

}
