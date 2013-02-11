<?php

namespace Wikibase\Test;

use Wikibase\ClaimDifference;

/**
 * Tests for the Wikibase\ClaimDifference class.
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
 * @ingroup WikibaseLib
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimDifferenceTest extends \MediaWikiTestCase {

	public function testGetReferenceChanges() {
		$expected = new \Diff\Diff( array(
			new \Diff\DiffOpAdd( new \Wikibase\Reference() )
		), false );

		$difference = new ClaimDifference( null, null, $expected );

		$actual = $difference->getReferenceChanges();

		$this->assertInstanceOf( 'Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetQualifierChanges() {
		$expected = new \Diff\Diff( array(
			new \Diff\DiffOpAdd( new \Wikibase\PropertyNoValueSnak( 42 ) )
		), false );

		$difference = new ClaimDifference( null, $expected );

		$actual = $difference->getQualifierChanges();

		$this->assertInstanceOf( 'Diff\Diff', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetMainSnakChange() {
		$expected = new \Diff\DiffOpChange(
			new \Wikibase\PropertyNoValueSnak( 42 ),
			new \Wikibase\PropertyNoValueSnak( 43 )
		);

		$difference = new ClaimDifference( $expected );

		$actual = $difference->getMainSnakChange();

		$this->assertInstanceOf( 'Diff\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

	public function testGetRankChange() {
		$expected = new \Diff\DiffOpChange(
			\Wikibase\Statement::RANK_PREFERRED,
			\Wikibase\Statement::RANK_DEPRECATED
		);

		$difference = new ClaimDifference( null, null, null, $expected );

		$actual = $difference->getRankChange();

		$this->assertInstanceOf( 'Diff\DiffOpChange', $actual );
		$this->assertEquals( $expected, $actual );
	}

}
