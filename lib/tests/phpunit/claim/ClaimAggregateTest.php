<?php

namespace Wikibase\Test;
use Wikibase\ClaimAggregate;
use Wikibase\Claim;

/**
 * Tests for the Wikibase\ClaimAggregate implementing classes.
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
 * @since 0.2
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
class ClaimAggregateTest extends \MediaWikiTestCase {

	public function ClaimTestProvider() {
		$claims = array();

		$claims[] = new \Wikibase\ClaimObject( new \Wikibase\PropertyNoValueSnak( 42 ) );
		$claims[] = new \Wikibase\ClaimObject( new \Wikibase\PropertyValueSnak( 10, new \DataValues\StringValue( 'ohi' ) ) );

		$aggregates = array();

		$aggregates[] = \Wikibase\PropertyObject::newEmpty();
		$aggregates[] = \Wikibase\QueryObject::newEmpty();

		$argLists = array();

		/**
		 * @var ClaimAggregate $aggregate
		 */
		foreach ( $aggregates as $aggregate ) {
			foreach ( $claims as $claim ) {
				$argLists[] = array( clone $aggregate, array( $claim ) );
			}

			$argLists[] = array( clone $aggregate, $claims );
		}

		return $argLists;
	}

	/**
	 * @dataProvider ClaimTestProvider
	 *
	 * @param ClaimAggregate $aggregate
	 * @param array $claims
	 */
	public function testAllOfTheStuff( ClaimAggregate $aggregate, array $claims ) {
		$obtainedClaims = $aggregate->getClaims();
		$this->assertInstanceOf( '\Wikibase\Claims', $obtainedClaims );

		// Below code tests if the Claims in the ClaimAggregate indeed do not get modified.

		$unmodifiedClaims = serialize( $obtainedClaims );

		$qualifiers = new \Wikibase\SnakList( array( new \Wikibase\PropertyValueSnak( 10, new \DataValues\StringValue( 'ohi' ) ) ) );

		/**
		 * @var Claim $claim
		 */
		foreach ( $obtainedClaims as $claim ) {
			$claim->setQualifiers( $qualifiers );
		}

		foreach ( $claims as $claim ) {
			$obtainedClaims->addClaim( $claim );
		}

		$freshlyObtained = $aggregate->getClaims();

		$this->assertEquals( $unmodifiedClaims, serialize( $freshlyObtained ), 'Was able to modify claims via ClaimAggregate::getClaims' );
	}

}
