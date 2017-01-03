<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the GeoData extension.
 * @codingStandardsIgnoreFile
 */

namespace GeoData;

class Coord {
	/**
	 * @param float $lat
	 * @param float $lon
	 * @param string|null $globe
	 * @param array $extraFields
	 */
	public function __construct( $lat, $lon, $globe = null, $extraFields = [] ) {
	}
}

class CoordinatesOutput {
	public function addPrimary( Coord $c ) {
	}

	/**
	 * @return Coord|false
	 */
	public function getPrimary() {
	}

	public function addSecondary( Coord $c ) {
	}
}

class GeoData {
}
