<?php

namespace Wikibase\Test;

use Wikibase\ClaimListAccess;

/**
 * Tests for the Wikibase\ClaimListAccess implementing classes.
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
 * @group WikibaseDataModel
 * @group WikibaseClaim
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ClaimListAccessTest extends \PHPUnit_Framework_TestCase {

	public function claimTestProvider() {
		$claims = array();

		$claims[] = new \Wikibase\Claim( new \Wikibase\PropertyNoValueSnak(
			new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 42 )
		) );
		$claims[] = new \Wikibase\Claim( new \Wikibase\PropertyValueSnak(
			new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 23 ),
			new \DataValues\StringValue( 'ohi' )
		) );

		$lists = array();

		$lists[] = new \Wikibase\Claims();

		$argLists = array();

		/**
		 * @var ClaimListAccess $list
		 */
		foreach ( $lists as $list ) {
			foreach ( $claims as $claim ) {
				$argLists[] = array( clone $list, array( $claim ) );
			}

			$argLists[] = array( clone $list, $claims );
		}

		return $argLists;
	}

	/**
	 * @dataProvider claimTestProvider
	 *
	 * @param ClaimListAccess $list
	 * @param array $claims
	 */
	public function testAllOfTheStuff( ClaimListAccess $list, array $claims ) {
		foreach ( $claims as $claim ) {
			$list->addClaim( $claim );
			$this->assertTrue( $list->hasClaim( $claim ) );

			$list->removeClaim( $claim );
			$this->assertFalse( $list->hasClaim( $claim ) );
		}
	}

}
