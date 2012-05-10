<?php

class WikibaseMapDiff implements Serializable, Iterator {

	/**
	 * @var array
	 */
	protected $items;

	/**
	 * @var integer
	 */
	protected  $key;

	/**
	 * @var array
	 */
	protected $current;

	protected function __construct( array $items ) {
		$this->items = $items;
		$this->key = 0;
		$this->setCurrent( $this->res->current() );
	}

	public function serialize() {
		return $this->items;
	}

	public function unserialize( $serialization ) {
		$this->items = $serialization;
	}

	public static function newEmpty() {
		return new self( array() );
	}

	public static function newFromArrays( array $oldValues, array $newValues, $emptyValue = null, $recursively = false ) {
		return new self( self::doDiff( $oldValues, $newValues, $emptyValue, $recursively ) );
	}

	/**
	 * Computes the diff between two associate arrays.
	 *
	 * @since 0.1
	 *
	 * @param array $oldValues The first array
	 * @param array $newValues The second array
	 * @param mixed $emptyValue The value to use to indicate the element did not exist in the old or new version
	 * @param boolean $recursively If elements that are arrays should also be diffed.
	 *
	 * @return array
	 * Each key existing in either array will exist in the result and have an array as value.
	 * This value is an array with two keys: old and new.
	 * Example:
	 * array(
	 * 'en' => array( 'old' => 'Foo', 'new' => 'Foobar' ),
	 * 'de' => array( 'old' => 42, 'new' => 9001 ),
	 * )
	 */
	public static function doDiff( array $oldValues, array $newValues, $emptyValue = null, $recursively = false ) {
		$oldSet = array_diff_assoc( $oldValues, $newValues );
		$newSet = array_diff_assoc( $newValues, $oldValues );

		$diffSet = array();

		foreach ( array_merge( array_keys( $oldSet ), array_keys( $newSet ) ) as $siteId ) {
			$hasOld = array_key_exists( $siteId, $oldSet );
			$hasNew = array_key_exists( $siteId, $newSet );

			if ( $recursively && $hasOld && $hasNew && is_array( $oldSet[$siteId] ) && is_array( $newSet[$siteId] ) ) {
				$elementDiff = self::arrayDiff( $oldSet[$siteId], $newSet[$siteId] );
				$oldSet[$siteId] = $elementDiff['old'];
				$newSet[$siteId] = $elementDiff['new'];
			}

			$diffSet[$siteId] = array(
				'old' => $hasOld ? $oldSet[$siteId] : $emptyValue,
				'new' => $hasNew ? $newSet[$siteId] : $emptyValue,
			);
		}

		return $diffSet;
	}

	/**
	 * @param $row
	 */
	protected function setCurrent( $row ) {
		if ( $row === false ) {
			$this->current = false;
		} else {
			$this->current = $this->table->newFromDBResult( $row );
		}
	}

	/**
	 * @return integer
	 */
	public function count() {
		return $this->res->numRows();
	}

	/**
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->res->numRows() === 0;
	}

	/**
	 * @return array
	 */
	public function current() {
		return $this->current;
	}

	/**
	 * @return integer
	 */
	public function key() {
		return $this->key;
	}

	public function next() {
		$row = $this->res->next();
		$this->setCurrent( $row );
		$this->key++;
	}

	public function rewind() {
		$this->res->rewind();
		$this->key = 0;
		$this->setCurrent( $this->res->current() );
	}

	/**
	 * @return boolean
	 */
	public function valid() {
		return $this->current !== false;
	}

}