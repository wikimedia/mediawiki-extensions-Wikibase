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
		return new self( self::doDiff( $oldValues, $newValues, $recursively ) );
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
		$oldSet = array_diff_assoc( $oldValues, $newValues );
		$newSet = array_diff_assoc( $newValues, $oldValues );

		$diffSet = array();

		foreach ( array_merge( array_keys( $oldSet ), array_keys( $newSet ) ) as $key ) {
			$hasOld = array_key_exists( $key, $oldSet );
			$hasNew = array_key_exists( $key, $newSet );

			if ( $recursively ) {
				if ( ( !$hasOld || is_array( $oldSet[$key] ) ) && ( !$hasNew || is_array( $newSet[$key] ) ) ) {
					$old = $hasOld ? $oldSet[$key] : array();
					$new = $hasNew ? $newSet[$key] : array();

					if ( self::isAssociative( $old ) || self::isAssociative( $new ) ) {
						$diffSet[$key] = self::newFromArrays( $old, $new );
					}
					else {
						$diffSet[$key] = ListDiff::newFromArrays( $old, $new );
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
	 * @since 0.1
	 * @return DiffOpList
	 */
	public function getChanges() {
		return $this->getTypeOperations( 'change' );
	}

}