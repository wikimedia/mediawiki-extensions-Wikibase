<?php

interface IWikibaseDiffOp {

	public function getType();

	//public function toArray();

}

abstract class WikibaseDiffOp implements IWikibaseDiffOp {

	/**
	 * Returns a new IWikibaseDiffOp implementing instance to represent the provided change.
	 *
	 * @since 0.1
	 *
	 * @param array $array
	 *
	 * @return WikibaseDiffOpAdd|WikibaseDiffOpChange|WikibaseDiffOpRemove
	 * @throws MWException
	 */
	public static function newFromArray( array $array ) {
		$type = array_shift( $array );

		$typeMap = array(
			'add' => 'WikibaseDiffOpAdd',
			'remove' => 'WikibaseDiffOpRemove',
			'change' => 'WikibaseDiffOpChange',
		);

		if ( !array_key_exists( $type, $typeMap ) ) {
			throw new MWException( 'Invalid diff type provided.' );
		}

		return call_user_func_array( array( $typeMap[$type], '__construct' ), $array );
	}

}

class WikibaseDiffOpAdd extends WikibaseDiffOp {

	protected $newValue;

	public function getType() {
		return 'add';
	}

	public function __construct( $newValue ) {
		$this->newValue = $newValue;
	}

	public function getNewValue() {
		return $this->newValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->newValue,
		);
	}

}

class WikibaseDiffOpRemove extends WikibaseDiffOp {

	protected $oldValue;

	public function getType() {
		return 'remove';
	}

	public function __construct( $oldValue ) {
		$this->oldValue = $oldValue;
	}

	public function getOldValue() {
		return $this->oldValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->oldValue,
		);
	}

}

class WikibaseDiffOpChange extends WikibaseDiffOp {

	protected $newValue;
	protected $oldValue;

	public function getType() {
		return 'change';
	}

	public function __construct( $oldValue, $newValue ) {
		$this->oldValue = $oldValue;
		$this->newValue = $newValue;
	}

	public function getOldValue() {
		return $this->oldValue;
	}

	public function getNewValue() {
		return $this->newValue;
	}

	public function toArray() {
		return array(
			$this->getType(),
			$this->newValue,
			$this->oldValue,
		);
	}

}

interface IWikibaseDiff {

	function __construct( array $operations );

	function getAdditions();

	function getRemovals();

}

class WikibaseDiffOpList extends ArrayIterator implements IWikibaseDiff {

	protected $parentKey;

	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
		'change' => array(),
	);

	/**
	 * @var array
	 */
	protected $operations = array();

	/**
	 * @var integer
	 */
	protected $key;

	/**
	 * @param array $operations Operations in array format
	 * @param string|integer|null $parentKey
	 */
	public function __construct( array $operations, $parentKey = null ) {
		$this->parentKey = $parentKey;
		$this->addOperations( $operations );
		$this->rewind();
	}

	/**
	 * @param string $type
	 * @return WikibaseDiffOpList
	 */
	protected function getTypeOperations( $type ) {
		return new WikibaseDiffOpList( array_intersect_key(
			$this->operations,
			array_flip( $this->typePointers[$type] )
		) );
	}

	protected function addOperations( array $operations ) {
		$this->operations = $operations;
	}

	protected function addTypedOperations( array $operations ) {
		foreach ( $operations as $key => $operation ) {
			if ( array_key_exists( $operation->getType(), $this->typePointers ) ) {
				$this->typePointers[$operation->getType()][] = $key;
			}
			else {
				throw new MWException( 'Diff operation with invalid type provided.' );
			}
		}
	}

	public function getParentKey() {
		return $this->parentKey;
	}

	public function hasParentKey() {
		return !is_null( $this->parentKey );
	}

	/**
	 * @since 0.1
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->operations, $this->typePointers ) );
	}

	/**
	 * @since 0.1
	 * @param string $serialization
	 */
	public function unserialize( $serialization ) {
		list( $this->operations, $this->typePointers ) = unserialize( $serialization );
	}

	/**
	 * @since 0.1
	 * @return integer
	 */
	public function count() {
		return count( $this->operations );
	}

	/**
	 * @since 0.1
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->operations === array();
	}

	/**
	 * @since 0.1
	 * @return IWikibaseDiffOp
	 */
	public function current() {
		return $this->operations[$this->key];
	}

	/**
	 * @since 0.1
	 * @return mixed
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * @since 0.1
	 */
	public function next() {
		next( $this->operations );
		$this->key = key( $this->operations );
	}

	/**
	 * @since 0.1
	 */
	public function rewind() {
		reset( $this->operations );
		$this->key = key( $this->operations );
	}

	/**
	 * @since 0.1
	 * @return boolean
	 */
	public function valid() {
		return $this->key !== false && isset( $this->operations[$this->key] );
	}

	/**
	 * @since 0.1
	 * @return WikibaseDiffOpList
	 */
	public function getAdditions() {
		return $this->getTypeOperations( 'add' );
	}

	/**
	 * @since 0.1
	 * @return WikibaseDiffOpList
	 */
	public function getRemovals() {
		return $this->getTypeOperations( 'remove' );
	}

}


class WikibaseMapDiff extends WikibaseDiffOpList implements IWikibaseDiffOp {

	public function getType() {
		return 'map';
	}

	protected function addOperations( array $operations ) {
		parent::addOperations( $operations );
		$this->addTypedOperations( $operations );
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
	 * @param boolean $recursively If elements that are arrays should also be diffed.
	 * @param array|boolean $lists
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
	public static function doDiff( array $oldValues, array $newValues, $recursively = false, $lists = false ) {
		$oldSet = array_diff_assoc( $oldValues, $newValues );
		$newSet = array_diff_assoc( $newValues, $oldValues );

		$diffSet = array();

		foreach ( array_merge( array_keys( $oldSet ), array_keys( $newSet ) ) as $key ) {
			$hasOld = array_key_exists( $key, $oldSet );
			$hasNew = array_key_exists( $key, $newSet );

			if ( $recursively ) {
				if ( ( $lists === true || ( is_array( $lists ) && in_array( $key, $lists ) ) )
					&& ( ( $hasOld && is_array( $oldSet[$key] ) ) || ( $hasNew && is_array( $newSet[$key] ) ) ) ) {

					$old = $hasOld ? $oldSet[$key] : array();
					$new = $hasNew ? $newSet[$key] : array();

					if ( is_array( $old ) && is_array( $new ) ) {
						$diff = new WikibaseListDiff( $old, $new );

					}
				}
				else if ( $hasOld && $hasNew && is_array( $oldSet[$key] ) && is_array( $newSet[$key] ) ) {
					$elementDiff = self::arrayDiff( $oldSet[$key], $newSet[$key] );
					$oldSet[$key] = $elementDiff['old'];
					$newSet[$key] = $elementDiff['new'];
				}
			}

			$diffSet[$key] = array();

			if ( $hasOld && $hasNew ) {
				$diffSet[$key] = array(
					'change',
					$oldSet[$key],
					$newSet[$key]
				);
			}
			elseif ( $hasOld ) {
				$diffSet[$key] = array(
					'remove',
					$oldSet[$key],
				);
			}
			elseif ( $hasNew ) {
				$diffSet[$key] = array(
					'add',
					$newSet[$key]
				);
			}
			else {
				throw new MWException( 'Cannot create a diff op for two empty values.' );
			}
		}

		return $diffSet;
	}

	/**
	 * @since 0.1
	 * @return WikibaseDiffOpList
	 */
	public function getChanges() {
		return $this->getTypeOperations( 'change' );
	}

}