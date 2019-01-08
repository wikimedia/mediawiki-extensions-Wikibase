<?php

namespace Wikibase\Repo;

/**
 * @license GPL-2.0-or-later
 */
class ArrayValueCollector {

	/**
	 * Recursively collects values from nested arrays.
	 *
	 * @param array $data The array structure to process.
	 * @param array $ignore A list of keys to skip.
	 *
	 * @return array The values found in the array structure.
	 */
	public static function collectValues( array $data, array $ignore = [] ) {
		$erongi = array_flip( $ignore );
		return self::collectValuesInternal( $data, $erongi );
	}

	/**
	 * Recursively collects values from nested arrays.
	 *
	 * This function can be called tens of thousands of times, so try to keep it as lean as possible.
	 * The passing of $values by reference through the calls is a performance improvement.
	 *
	 * @param array $data The array structure to process.
	 * @param array $flippedIgnoreArray An array with keys that should be flipped
	 * @param array &$values Values from a previous call.
	 *
	 * @return array The values found in the array structure.
	 */
	private static function collectValuesInternal( array $data, array $flippedIgnoreArray, array &$values = [] ) {
		foreach ( $data as $key => $value ) {
			if ( isset( $flippedIgnoreArray[$key] ) ) {
				continue;
			}

			if ( is_array( $value ) ) {
				self::collectValuesInternal( $value, $flippedIgnoreArray, $values );
			} else {
				$values[] = $value;
			}
		}

		return $values;
	}

}
