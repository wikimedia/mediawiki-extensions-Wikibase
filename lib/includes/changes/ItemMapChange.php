<?php

namespace Wikibase;

/**
 * Class representing a change that can be represented as a MapDiff to an item.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemMapChange extends ItemChange {

	/**
	 * @since 0.1
	 *
	 * @return MapDiff
	 * @throws \MWException
	 */
	public function getDiff() {
		$info = $this->getField( 'info' );

		if ( !array_key_exists( 'diff', $info ) ) {
			throw new \MWException( 'Cannot get the diff when it has not been set yet.' );
		}

		return $info['diff'];
	}

	/**
	 * @since 0.1
	 *
	 * @param MapDiff $diff
	 */
	public function setDiff( MapDiff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		return $this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Item $item
	 * @param MapDiff $diff
	 * @param array|null $fields
	 *
	 * @return ItemMapChange
	 */
	public static function newFromDiff( Item $item, MapDiff $diff, array $fields = null ) {
		$instance = new static(
			Changes::singleton(),
			$fields,
			true
		);

		$instance->setItem( $item );
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
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'diff', $info ) ) {
				return $this->getDiff()->isEmpty();
			}
		}

		return true;
	}

}