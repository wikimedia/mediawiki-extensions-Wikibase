<?php

namespace Wikibase\Test;

use Diff\CallbackListDiffer;
use Diff\Diff;
use Diff\DiffOpAdd;
use Diff\DiffOpChange;
use Diff\DiffOpRemove;
use Wikibase\ClaimDiffer;
use Wikibase\ClaimDifference;
use Wikibase\Claim;
use Wikibase\PropertyNoValueSnak;
use Wikibase\ReferenceList;
use Wikibase\SnakList;
use Wikibase\Statement;

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

		$noValueForP42 = new Statement( new PropertyNoValueSnak( 42 ) );
		$noValueForP43 = new Statement( new PropertyNoValueSnak( 43 ) );

		$argLists[] = array(
			$noValueForP42,
			$noValueForP42,
			new ClaimDifference()
		);

		$argLists[] = array(
			$noValueForP42,
			$noValueForP43,
			new ClaimDifference( new DiffOpChange( new PropertyNoValueSnak( 42 ), new PropertyNoValueSnak( 43 ) ) )
		);

		$qualifiers = new SnakList( array( new PropertyNoValueSnak( 1 ) ) );
		$withQualifiers = clone $noValueForP42;
		$withQualifiers->setQualifiers( $qualifiers );

		$argLists[] = array(
			$noValueForP42,
			$withQualifiers,
			new ClaimDifference(
				null,
				new Diff( array(
					new DiffOpAdd( new PropertyNoValueSnak( 1 ) )
				), false )
			)
		);

		$references = new ReferenceList( array( new PropertyNoValueSnak( 2 ) ) );
		$withReferences = clone $noValueForP42;
		$withReferences->setReferences( $references );

		$argLists[] = array(
			$noValueForP42,
			$withReferences,
			new ClaimDifference(
				null,
				null,
				new Diff( array(
					new DiffOpAdd( new PropertyNoValueSnak( 2 ) )
				), false )
			)
		);

		$argLists[] = array(
			$withQualifiers,
			$withReferences,
			new ClaimDifference(
				null,
				new Diff( array(
					new DiffOpRemove( new PropertyNoValueSnak( 1 ) )
				), false ),
				new Diff( array(
					new DiffOpAdd( new PropertyNoValueSnak( 2 ) )
				), false )
			)
		);

		$noValueForP42Preferred = clone $noValueForP42;
		$noValueForP42Preferred->setRank( Statement::RANK_PREFERRED );

		$argLists[] = array(
			$noValueForP42,
			$noValueForP42Preferred,
			new ClaimDifference(
				null,
				null,
				null,
				new DiffOpChange( Statement::RANK_NORMAL, Statement::RANK_PREFERRED )
			)
		);

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
		$comparer = function( \Comparable $old, \Comparable $new ) {
			return $old->equals( $new );
		};

		$differ = new ClaimDiffer( new CallbackListDiffer( $comparer ) );
		$actual = $differ->diffClaims( $oldClaim, $newClaim );

		$this->assertInstanceOf( 'Wikibase\ClaimDifference', $actual );

		if ( !$expected->equals( $actual ) ) {
			q($expected, $actual);
		}

		$this->assertTrue(
			$expected->equals( $actual ),
			'Diffing the claims results in the correct ClaimDifference'
		);
	}

}
