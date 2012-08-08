<?php

namespace Wikibase;
use Diff\IDiff as IDiff;

/**
 * Class for changes that can be represented as a IDiff.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class DiffChange extends ChangeRow {

	/**
	 * @since 0.1
	 *
	 * @return IDiff
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
	 * @param IDiff $diff
	 */
	public function setDiff( IDiff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param IDiff $diff
	 * @param array|null $fields
	 *
	 * @return DiffChange
	 */
	public static function newFromDiff( IDiff $diff, array $fields = null ) {
		$instance = new static(
			ChangesTable::singleton(),
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
		if ( $this->hasField( 'info' ) ) {
			$info = $this->getField( 'info' );

			if ( array_key_exists( 'diff', $info ) ) {
				return $this->getDiff()->isEmpty();
			}
		}

		return true;
	}

}