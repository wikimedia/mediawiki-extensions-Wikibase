<?php

namespace Wikibase;

interface IDiff {

	function __construct( array $operations );

	function getAdditions();

	function getRemovals();

}

class DiffOpList extends \ArrayIterator implements IDiff {

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
	 * @return DiffOpList
	 */
	protected function getTypeOperations( $type ) {
		return new DiffOpList( array_intersect_key(
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
	 * @return DiffOpList
	 */
	public function getAdditions() {
		return $this->getTypeOperations( 'add' );
	}

	/**
	 * @since 0.1
	 * @return DiffOpList
	 */
	public function getRemovals() {
		return $this->getTypeOperations( 'remove' );
	}

}