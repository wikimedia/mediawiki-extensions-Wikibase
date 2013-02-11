<?php

namespace Wikibase\Test;

use Wikibase\ClaimDiffer;
use Wikibase\ClaimDifference;
use Wikibase\Claim;

/**
 * Tests for the Wikibase\ClaimDiffer class.
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
class ClaimDifferTest extends \MediaWikiTestCase {

	public function diffClaimsProvider() {
		$argLists = array();

		$simpleClaim = new Claim( new \Wikibase\PropertyNoValueSnak( 42 ) );

		$argLists[] = array( $simpleClaim, $simpleClaim, new ClaimDifference() );

		// TODO: more tests

		return $argLists;
	}

	/**
	 * @dataProvider diffClaimsProvider
	 *
	 * @param Claim $oldClaim
	 * @param Claim $newClaim
	 * @param ClaimDifference $expected
	 */
	public function testDiffClaims( Claim $oldClaim, Claim $newClaim, ClaimDifference $expected ) {
		$differ = new ClaimDiffer( new \Diff\ListDiffer() );
		$actual = $differ->diffClaims( $oldClaim, $newClaim );

		$this->assertInstanceOf( 'Wikibase\ClaimDifference', $actual );
		$this->assertTrue( $expected->equals( $actual ), 'Expected equals actual' );
	}

}
