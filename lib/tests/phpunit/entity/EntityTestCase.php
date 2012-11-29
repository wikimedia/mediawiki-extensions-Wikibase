<?php

namespace Wikibase\Test;
use Wikibase\EntityFactory;
use Wikibase\Entity;
use Wikibase\Item;
use Wikibase\Property;
use Wikibase\Query;

/**
 * Base class for tests that have to inspect entity structures.
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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class EntityTestCase extends \MediaWikiTestCase {

	/**
	 * @param Entity|array $expected
	 * @param Entity|array $actual
	 * @param String|null  $message
	 */
	protected function assertEntityStructureEquals( $expected, $actual, $message = null ) {
		if ( $expected instanceof Entity ) {
			$expected = $expected->toArray();
		}

		if ( $actual instanceof Entity ) {
			$actual = $actual->toArray();
		}

		$keys = array_unique( array_merge(
			array_keys( $expected ),
			array_keys( $actual ) ) );

		foreach ( $keys as $k ) {
			if ( empty( $expected[ $k ] ) ) {
				if ( !empty( $actual[ $k ] ) ) {
					$this->fail( "$k should be empty; $message" );
				}
			} else {
				if ( empty( $actual[ $k ] ) ) {
					$this->fail( "$k should not be empty; $message" );
				}

				$this->assertArrayEquals( $expected[ $k ], $actual[ $k ], false, true );
			}
		}
	}
}
