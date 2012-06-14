<?php

namespace Wikibase;

/**
 * Base class for diffs. Diffs are collections of IDiffOp objects.
 *
 * TODO: since this is an ArrayIterator, people can just add stuff using $diff[] = $diffOp.
 * The $typePointers is not currently getting updates in this case.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class Diff extends \ArrayIterator implements IDiff {

	protected $parentKey;

	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
		'change' => array(),
		'list' => array(),
		'map' => array(),
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
		parent::__construct( $operations );
		$this->parentKey = $parentKey;
		$this->addOperations( $operations );
		$this->rewind();
	}

	public function getOperations() {
		return $this->operations;
	}

	/**
	 * @param string $type
	 * @return array of DiffOp
	 */
	public function getTypeOperations( $type ) {
		return array_intersect_key(
			$this->operations,
			array_flip( $this->typePointers[$type] )
		);
	}

	protected function addOperations( array $operations ) {
		$this->operations = $operations;
		$this->addTypedOperations( $operations );
	}

	protected function addTypedOperations( array $operations ) {
		foreach ( $operations as $key => /* DiffOp */ $operation ) {
			if ( array_key_exists( $operation->getType(), $this->typePointers ) ) {
				$this->typePointers[$operation->getType()][] = $key;
			}
			else {
				throw new \MWException( 'Diff operation with invalid type provided.' );
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
	 * @return IDiffOp
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
	 * @return array of DiffOp
	 */
	public function getAdditions() {
		return $this->getTypeOperations( 'add' );
	}

	/**
	 * @since 0.1
	 * @return array of DiffOp
	 */
	public function getRemovals() {
		return $this->getTypeOperations( 'remove' );
	}

	/**
	 * @since 0.1
	 * @return array of mixed
	 */
	public function getAddedValues() {
		return array_map(
			function( DiffOpAdd $addition ) {
				return $addition->getNewValue();
			},
			$this->getTypeOperations( 'add' )
		);
	}

	/**
	 * @since 0.1
	 * @return array of mixed
	 */
	public function getRemovedValues() {
		return array_map(
			function( DiffOpRemove $addition ) {
				return $addition->getOldValue();
			},
			$this->getTypeOperations( 'remove' )
		);
	}

}