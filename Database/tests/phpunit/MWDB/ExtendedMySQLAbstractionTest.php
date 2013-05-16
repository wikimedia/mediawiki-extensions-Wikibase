<?php

namespace Wikibase\Database\Tests\MWDB;

use Wikibase\Database\LazyDBConnectionProvider;
use Wikibase\Database\MWDB\ExtendedMySQLAbstraction;

/**
 * @covers Wikibase\Database\MWDB\ExtendedMySQLAbstraction
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
 * @ingroup WikibaseDatabaseTest
 *
 * @group Wikibase
 * @group WikibaseDatabase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ExtendedMySQLAbstractionTest extends ExtendedAbstractionTest {

	protected function setUp() {
		if ( !function_exists( 'wfGetDB' ) || wfGetDB( DB_SLAVE )->getType() !== 'mysql' ) {
			$this->markTestSkipped( 'Can only run the ExtendedMySQLAbstractionTest when MediaWiki is using MySQL' );
		}

		parent::setUp();
	}

	/**
	 * @see ExtendedAbstractionTest::newInstance
	 *
	 * @return ExtendedMySQLAbstraction
	 */
	protected function newInstance() {
		return new ExtendedMySQLAbstraction( new LazyDBConnectionProvider( DB_MASTER ) );
	}

}
