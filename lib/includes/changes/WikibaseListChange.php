<?php

/**
 * Class representing a change that can be described as a list of additions and removals (ie a WikibaseListDiff).
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
abstract class WikibaseListChange extends WikibaseChange {

	/**
	 * @since 0.1
	 *
	 * @return WikibaseListDiff
	 */
	public function getDiff() {
		return $this->getField( 'info' );
	}

	/**
	 * @since 0.1
	 *
	 * @param WikibaseListDiff $diff
	 */
	public function setDiff( WikibaseListDiff $diff ) {
		return $this->setField( 'info', $diff );
	}

	/**
	 * @since 0.1
	 * .
	 * @param WikibaseListDiff $diff
	 * @param array|null $fields
	 *
	 * @return WikibaseSitelinkChange
	 */
	public static function newFromDiff( WikibaseListDiff $diff, array $fields = null ) {
		$instance = new static(
			WikibaseChanges::singleton(),
			$fields,
			true
		);

		$instance->setDiff( $diff );

		return $instance;
	}

}