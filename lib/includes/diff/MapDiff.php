<?php

namespace Wikibase;
use MWException;

class MapDiff extends Diff implements IDiffOp {

	public function getType() {
		return 'map';
	}

	public static function newEmpty() {
		return new self( array() );
	}

	public static function newFromArrays( array $oldValues, array $newValues, $recursively = false ) {
		return new self( static::doDiff( $oldValues, $newValues, $recursively ) );
	}

	/**
	 * Computes the diff between two associate arrays.
	 *
	 * @since 0.1
	 *
	 * @param array $oldValues The first array
	 * @param array $newValues The second array
	 * @param boolean $recursively If elements that are arrays should also be diffed.
	 *
	 * @throws MWException
	 * @return array
	 * Each key existing in either array will exist in the result and have an array as value.
	 * This value is an array with two keys: old and new.
	 * Example:
	 * array(
	 * 'en' => array( 'old' => 'Foo', 'new' => 'Foobar' ),
	 * 'de' => array( 'old' => 42, 'new' => 9001 ),
	 * )
	 */
	public static function doDiff( array $oldValues, array $newValues, $recursively = false ) {
		$newSet = static::array_diff_assoc( $newValues, $oldValues );
		$oldSet = static::array_diff_assoc( $oldValues, $newValues );

		$diffSet = array();

		foreach ( array_merge( array_keys( $oldSet ), array_keys( $newSet ) ) as $key ) {
			$hasOld = array_key_exists( $key, $oldSet );
			$hasNew = array_key_exists( $key, $newSet );

			if ( $recursively ) {
				if ( ( !$hasOld || is_array( $oldSet[$key] ) ) && ( !$hasNew || is_array( $newSet[$key] ) ) ) {

					$old = $hasOld ? $oldSet[$key] : array();
					$new = $hasNew ? $newSet[$key] : array();

					if ( static::isAssociative( $old ) || static::isAssociative( $new ) ) {
						$diff = static::newFromArrays( $old, $new );
					}
					else {
						$diff = ListDiff::newFromArrays( $old, $new );
					}

					if ( !$diff->isEmpty() ) {
						$diffSet[$key] = $diff;
					}

					continue;
				}
			}

			if ( $hasOld && $hasNew ) {
				$diffSet[$key] = new DiffOpChange( $oldSet[$key], $newSet[$key] );
			}
			elseif ( $hasOld ) {
				$diffSet[$key] = new DiffOpRemove( $oldSet[$key] );
			}
			elseif ( $hasNew ) {
				$diffSet[$key] = new DiffOpAdd( $newSet[$key] );
			}
			else {
				throw new MWException( 'Cannot create a diff op for two empty values.' );
			}
		}

		return $diffSet;
	}

	protected static function isAssociative( array $array ) {
		return array_keys( $array ) !== range( 0, count( $array ) - 1 );
	}

	/**
	 * Similar to the native array_diff_assoc function, except that it will
	 * spot differences between array values. Very weird the native
	 * function just ignores these...
	 *
	 * @see http://php.net/manual/en/function.array-diff-assoc.php
	 *
	 * @since 0.1
	 *
	 * @param array $from
	 * @param array $to
	 *
	 * @return array
	 */
	protected static function array_diff_assoc( array $from, array $to ) {
		$diff = array();

		foreach ( $from as $key => $value ) {
			if ( !array_key_exists( $key, $to ) || $to[$key] !== $value ) {
				$diff[$key] = $value;
			}
		}

		return $diff;

		return array_filter(
			$from,
			function( $value ) use ( $to ) {
				return !in_array( $value, $to );
			}
		);
	}

	/**
	 * @since 0.1
	 * @return DiffOpList
	 */
	public function getChanges() {
		return $this->getTypeOperations( 'change' );
	}

}