<?php

declare( strict_types = 1 );

namespace Wikibase\DataModel\Serializers;

/**
 * @license GPL-2.0-or-later
 */
abstract class MapSerializer {

	protected bool $useObjectsForEmptyMaps;

	public function __construct( bool $useObjectsForEmptyMaps ) {
		$this->useObjectsForEmptyMaps = $useObjectsForEmptyMaps;
	}

	/**
	 * Create the serialized representation of the supplied object
	 *
	 * Based on the $useObjectsForEmptyMaps flag, arrays in the
	 * serialized structure may be transformed into stdClass / objects.
	 * This helps ensure that in JSON serialization, PHP uses {}
	 * instead of [] for these dictionaries.
	 *
	 * Currently the flag is only set in limited cases, including for
	 * the creation of JSON Dumps (per T305660). Setting the flag in
	 * all cases has undesired interactions with other serialisation
	 * code.
	 *
	 * @return array|\stdClass
	 */
	protected function serializeMap( array $serialization ) {
		if ( $this->useObjectsForEmptyMaps && empty( $serialization ) ) {
			$serialization = (object)$serialization;
		}

		return $serialization;
	}
}
