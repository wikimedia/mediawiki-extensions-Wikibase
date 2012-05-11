<?php

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
abstract class WikibaseMapChange extends WikibaseChange {

	/**
	 * @since 0.1
	 *
	 * @return WikibaseMapDiff
	 */
	public function getDiff() {
		return $this->getField( 'info' );
	}

	/**
	 * @since 0.1
	 *
	 * @param WikibaseMapDiff $diff
	 */
	public function setDiff( WikibaseListDiff $diff ) {
		return $this->setField( 'info', $diff );
	}

	/**
	 * @since 0.1
	 *
	 * @param WikibaseMapDiff $diff
	 * @param array|null $fields
	 *
	 * @return WikibaseMapChange
	 */
	public static function newFromDiff( WikibaseMapDiff $diff, array $fields = null ) {
		$instance = new static(
			WikibaseChanges::singleton(),
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