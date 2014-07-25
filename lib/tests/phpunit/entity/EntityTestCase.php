<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\Entity;

/**
 * Base class for tests that have to inspect entity structures.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
abstract class EntityTestCase extends \MediaWikiTestCase {

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

				if ( is_array( $expected[ $k ] ) && is_array( $actual[ $k ] ) ) {
					$this->assertArrayEquals( $expected[ $k ], $actual[ $k ], false, true );
				} else {
					$this->assertEquals( $expected[ $k ], $actual[ $k ], "field $k" );
				}
			}
		}
	}
}
