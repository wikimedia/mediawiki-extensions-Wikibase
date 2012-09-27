<?php

namespace Wikibase\Test;
use Wikibase\Item as Item;
use Diff\MapDiff as MapDiff;

/**
 * Tests for the Wikibase\DiffChange class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseChange
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffChangeTest extends \MediaWikiTestCase {

	public function diffProvider() {
		return array(
			array( MapDiff::newEmpty() ),
			array( MapDiff::newFromArrays( array(), array( 'en' => 'foo' ) ) ),
			array( MapDiff::newFromArrays( array( 'en' => 'bar' ), array( 'en' => 'foo' ) ) ),
			array( MapDiff::newFromArrays( array( 'en' => 'bar' ), array( 'de' => 'bar' ) ) ),
		);
	}

	/**
	 * @param MapDiff $diff
	 * @dataProvider diffProvider
	 */
	public function testNewFromDiff( MapDiff $diff ) {
		$change = \Wikibase\DiffChange::newFromDiff( $diff );

		$this->assertEquals( $diff->isEmpty(), $change->isEmpty() );

		$change->setDiff( MapDiff::newEmpty() );

		$this->assertTrue( $change->isEmpty() );

		$diff = MapDiff::newFromArrays( array(), array( 'en' => 'foo' ) );

		$change->setDiff( $diff );

		$this->assertFalse( $change->isEmpty() );

		$this->assertEquals( $diff, $change->getDiff() );
	}

}
	
