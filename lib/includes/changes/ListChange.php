<?php

namespace Wikibase;

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
class ListChange extends Change {

	/**
	 * @since 0.1
	 *
	 * @return ListDiff
	 */
	public function getDiff() {
		return $this->getField( 'info' );
	}

	/**
	 * @since 0.1
	 *
	 * @param ListDiff $diff
	 */
	public function setDiff( ListDiff $diff ) {
		return $this->setField( 'info', $diff );
	}

	/**
	 * @since 0.1
	 * .
	 * @param ListDiff $diff
	 * @param array|null $fields
	 *
	 * @return SitelinkChange
	 */
	public static function newFromDiff( ListDiff $diff, array $fields = null ) {
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