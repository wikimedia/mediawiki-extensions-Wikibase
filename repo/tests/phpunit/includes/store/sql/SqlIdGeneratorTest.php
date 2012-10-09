<?php

namespace Wikibase\Test;
use Wikibase\IdGenerator as IdGenerator;

/**
 * Tests for the Wikibase\SqlIdGenerator class that are not in IdGeneratorTest.
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
class SqlIdGeneratorTest extends \MediaWikiTestCase {

	public function testIdBlacklisting() {
		/**
		 * @var IdGenerator $clone
		 */
		$generator = \Wikibase\StoreFactory::getStore( 'sqlstore' )->newIdGenerator();

		for ( $i = 0; $i < 45; ++$i ) {
			$this->assertFalse( in_array( $generator->getNewId( 'blacklisttest' ), \Wikibase\Settings::get( 'idBlacklist' ) ) );
		}
	}

}
