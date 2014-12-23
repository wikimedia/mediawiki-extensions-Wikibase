<?php

namespace Wikibase;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;

/**
 * Class for changes that can be represented as a Diff.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class DiffChange extends ChangeRow {

	/**
	 * @since 0.1
	 *
	 * @param string $cache set to 'cache' to cache the unserialized diff.
	 *
	 * @return Diff
	 */
	public function getDiff( $cache = 'no' ) {
		$info = $this->getInfo( $cache );

		if ( !array_key_exists( 'diff', $info ) ) {
			// This shouldn't happen, but we should be robust against corrupt, incomplete
			// obsolete instances in the database, etc.
			wfLogWarning( 'Cannot get the diff when it has not been set yet.' );
			return new Diff();
		} else {
			return $info['diff'];
		}
	}

	/**
	 * @since 0.4
	 *
	 * @return bool
	 */
	public function hasDiff() {
		$info = $this->getField( 'info' );
		return !empty( $info ) && isset( $info['diff'] );
	}

	/**
	 * @since 0.1
	 *
	 * @param Diff $diff
	 */
	public function setDiff( Diff $diff ) {
		$info = $this->hasField( 'info' ) ? $this->getField( 'info' ) : array();
		$info['diff'] = $diff;
		$this->setField( 'info', $info );
	}

	/**
	 * @since 0.1
	 *
	 * @param Diff $diff
	 * @param array|null $fields
	 *
	 * @return DiffChange
	 */
	public static function newFromDiff( Diff $diff, array $fields = null ) {
		/**
		 * @var self $instance
		 */
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

	/**
	 * @see ChangeRow::serializeInfo()
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @since 0.4
	 * @param array $info
	 * @return string
	 */
	public function serializeInfo( array $info ) {
		if ( isset( $info['diff'] ) && $info['diff'] instanceof DiffOp ) {
			/* @var \Diff\DiffOp $op */
			$op = $info['diff'];
			$info['diff'] = $op->toArray( array( $this, 'arrayalizeObjects' ) );
		}

		return parent::serializeInfo( $info );
	}

	/**
	 * @see ChangeRow::unserializeInfo()
	 *
	 * Overwritten to use the array representation of the diff.
	 *
	 * @since 0.4
	 * @param string $str
	 * @return array the info array
	 */
	public function unserializeInfo( $str ) {
		static $factory = null;

		if ( $factory == null ) {
			$factory = new WikibaseDiffOpFactory( array( $this, 'objectifyArrays' ) );
		}

		$info = parent::unserializeInfo( $str );

		if ( isset( $info['diff'] ) && is_array( $info['diff'] ) ) {
			$info['diff'] = $factory->newFromArray( $info['diff'] );
		}

		return $info;
	}

	/**
	 * Converts an object to an array structure.
	 * Callback function for use by \Diff\DiffOp::toArray().
	 *
	 * Subclasses should override this to provide array representations of specific value objects.
	 *
	 * @since 0.4
	 *
	 * @param mixed $data
	 * @return mixed
	 */
	public function arrayalizeObjects( $data ) {
		return $data; // noop
	}

	/**
	 * May be overwritten by subclasses to provide special handling.
	 * Callback function for use by \Diff\DiffOpFactory
	 *
	 * Subclasses should override this to reconstruct value objects from arrays.
	 *
	 * @since 0.4
	 *
	 * @param array $data
	 * @return mixed
	 */
	public function objectifyArrays( array $data ) {
		return $data; // noop
	}
}
