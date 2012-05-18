<?php

namespace Wikibase;

/**
 * Class representing a change that can be described as a list of changes to named items (ie a WikibaseMapDiff).
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class MapChange extends Change {

	/**
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getDiff() {
		return $this->getField( 'info' );
	}

	/**
	 * @since 0.1
	 *
	 * @param MapDiff $diff
	 */
	public function setDiff( MapDiff $diff ) {
		return $this->setField( 'info', $diff );
	}

	/**
	 * @since 0.1
	 *
	 * @param MapDiff $diff
	 * @param array|null $fields
	 *
	 * @return MapChange
	 */
	public static function newFromDiff( MapDiff $diff, array $fields = null ) {
		$instance = new static(
			Changes::singleton(),
			$fields,
			true
		);

		$instance->setDiff( $diff );

		return $instance;
	}

	/**
	 * Returns whether the change is empty.
	 * If it's empty, it can be ignored.
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->getDiff()->isEmpty();
	}

}