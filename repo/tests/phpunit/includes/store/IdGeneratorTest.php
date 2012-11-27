<?php

namespace Wikibase\Test;
use Wikibase\IdGenerator;

/**
 * Tests for the Wikibase\IdGenerator implementing classes.
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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class IdGeneratorTest extends \MediaWikiTestCase {

	public function instanceProvider() {
		$instances = array( \Wikibase\StoreFactory::getStore( 'sqlstore' )->newIdGenerator() );

		return $this->arrayWrap( $instances );
	}

	/**
	 * @dataProvider instanceProvider
	 */
	public function testGetNewId( IdGenerator $generator ) {
		/**
		 * @var IdGenerator $clone
		 */
		$clone = clone $generator;

		$id = $generator->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id );

		$id1 = $generator->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id1 );
		$this->assertNotEquals( $id, $id1 );

		$id2 = $generator->getNewId( 'bar' );
		$this->assertInternalType( 'integer', $id2 );

		$id3 = $clone->getNewId( 'foo' );

		$this->assertInternalType( 'integer', $id3 );

		$this->assertTrue( !in_array( $id3, array( $id, $id1 ), true ) );
	}

}
