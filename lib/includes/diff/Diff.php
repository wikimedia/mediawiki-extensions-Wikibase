<?php

namespace Wikibase;

/**
 * Base class for diffs. Diffs are collections of IDiffOp objects,
 * and are themselves IDiffOp objects as well.
 *
 * TODO: since this is an ArrayIterator, people can just add stuff using $diff[] = $diffOp.
 * The $typePointers is not currently getting updates in this case.
 * FIXME: should use ArrayObject
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
abstract class Diff extends \ArrayIterator implements IDiff {

	/**
	 * Key the operation has in it's parent diff.
	 *
	 * @since 0.1
	 *
	 * @var string|integer|null
	 */
	protected $parentKey;

	/**
	 * Pointers to the operations of certain types for quick lookup.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
		'change' => array(),
		'list' => array(),
		'map' => array(),
	);

	/**
	 * The collection of opertaions that make up the diff.
	 *
	 * @since 0.1
	 *
	 * @var array of IDiffOp
	 */
	protected $operations = array();

	/**
	 * The internal pointer. @see ArrayIterator
	 *
	 * @since 0.1
	 *
	 * @var string|integer|null
	 */
	protected $key;

	/**
	 * @see IDiff::__construct
	 *
	 * @since 0.1
	 *
	 * @param array $operations Operations in array format
	 * @param string|integer|null $parentKey
	 */
	public function __construct( array $operations, $parentKey = null ) {
		parent::__construct( $operations );
		$this->parentKey = $parentKey;
		$this->addOperations( $operations );
		$this->rewind();
	}

	/**
	 * @see IDiff::getOperations
	 *
	 * @since 0.1
	 *
	 * @return array of IDiffOp
	 */
	public function getOperations() {
		return $this->operations;
	}

	/**
	 * @since 0.1
	 *
	 * @param string $type
	 *
	 * @return array of DiffOp
	 */
	public function getTypeOperations( $type ) {
		return array_intersect_key(
			$this->operations,
			array_flip( $this->typePointers[$type] )
		);
	}

	/**
	 * @see IDiff::addOperations
	 *
	 * @since 0.1
	 *
	 * @param $operations array of IDiffOp
	 */
	public function addOperations( array $operations ) {
		$this->addTypedOperations( $operations );
		$this->operations = $operations;
	}

	/**
	 * @since 0.1
	 *
	 * @param $operations array of IDiffOp
	 * @throws \MWException
	 */
	protected function addTypedOperations( array $operations ) {
		foreach ( $operations as $key => /* DiffOp */ $operation ) {
			if ( array_key_exists( $operation->getType(), $this->typePointers ) ) {
				$this->typePointers[$operation->getType()][] = $key;
			}
			else {
				throw new \MWException( 'Diff operation with invalid type "' . $operation->getType() . '" provided.' );
			}
		}
	}

	/**
	 * @see IDiff::getParentKey
	 *
	 * @since 0.1
	 *
	 * @return int|null|string
	 */
	public function getParentKey() {
		return $this->parentKey;
	}

	/**
	 * @see IDiff::hasParentKey
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function hasParentKey() {
		return !is_null( $this->parentKey );
	}

	/**
	 * @since 0.1
	 *
	 * @return string
	 */
	public function serialize() {
		return serialize( array( $this->operations, $this->typePointers ) );
	}

	/**
	 * @since 0.1
	 *
	 * @param string $serialization
	 */
	public function unserialize( $serialization ) {
		list( $this->operations, $this->typePointers ) = unserialize( $serialization );
	}

	/**
	 * Returns the number of operations the diff contains.
	 *
	 * @since 0.1
	 *
	 * @return integer
	 */
	public function count() {
		return count( $this->operations );
	}

	/**
	 * @see IDiff::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return $this->operations === array();
	}

	/**
	 * Returns the current operation.
	 *
	 * @since 0.1
	 *
	 * @return IDiffOp
	 */
	public function current() {
		return $this->operations[$this->key];
	}

	/**
	 * Returns the key of the current operation.
	 *
	 * @since 0.1
	 *
	 * @return mixed
	 */
	public function key() {
		return $this->key;
	}

	/**
	 * @see ArrayIterator::next
	 *
	 * @since 0.1
	 */
	public function next() {
		next( $this->operations );
		$this->key = key( $this->operations );
	}

	/**
	 * @see ArrayIterator::rewind
	 *
	 * @since 0.1
	 */
	public function rewind() {
		reset( $this->operations );
		$this->key = key( $this->operations );
	}

	/**
	 * @see ArrayIterator::valid
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function valid() {
		return $this->key !== false && isset( $this->operations[$this->key] );
	}

	/**
	 * Returns the add operations.
	 *
	 * @since 0.1
	 *
	 * @return array of DiffOpAdd
	 */
	public function getAdditions() {
		return $this->getTypeOperations( 'add' );
	}

	/**
	 * Returns the remove operations.
	 *
	 * @since 0.1
	 *
	 * @return array of DiffOpRemove
	 */
	public function getRemovals() {
		return $this->getTypeOperations( 'remove' );
	}

	/**
	 * Returns the added values.
	 *
	 * @since 0.1
	 *
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
	 * Returns the removed values.
	 *
	 * @since 0.1
	 *
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

	/**
	 * @see IDiff::getApplicableDiff
	 *
	 * @since 0.1
	 *
	 * @param array $currentObject
	 *
	 * @return IDiff
	 */
	public function getApplicableDiff( array $currentObject ) {
		$undoDiff = static::newEmpty();
		static::addReversibleOperations( $undoDiff, $this, $currentObject );
		return $undoDiff;
	}

	/**
	 * Checks the operations in $originDiff for reversibility and adds those that are reversible to $diff.
	 *
	 * @since 0.1
	 *
	 * @param IDiff $diff The diff to add the reversible operations to
	 * @param IDiff $originDiff The diff with the operations we want to check reversibility for
	 * @param array $currentObject An array with the current structure used to check reversibility
	 *
	 * @return IDiff
	 */
	protected function addReversibleOperations( IDiff &$diff, IDiff $originDiff, array $currentObject ) {
		/**
		 * @var IDiffOp $diffOp
		 */
		foreach ( $originDiff as $key => $diffOp ) {
			if ( $originDiff->getType() === 'list' || array_key_exists( $key, $currentObject ) ) {
				if ( $diffOp->isAtomic() ) {
					if ( $originDiff->getType() === 'list' ) {
						$isRemove = $diffOp->getType() === 'remove';
						$value = $isRemove ? $diffOp->getOldValue() : $diffOp->getNewValue();

						if ( $isRemove === in_array( $value, $currentObject ) ) {
							$diff->addOperations( array( $diffOp ) );
						}
					}
					else {
						$canApplyOp =
							( $diffOp->getType() === 'add' && !array_key_exists( $key, $currentObject ) )
								|| ( array_key_exists( $key, $currentObject ) && $currentObject[$key] === $diffOp->getOldValue() );

						if ( $canApplyOp ) {
							$diff->addOperations( array( $key => $diffOp ) );
						}
					}
				}
				else {
					$childDiff = $originDiff->getType() === 'map' ? MapDiff::newEmpty( $key ) : ListDiff::newEmpty( $key );
					$this->addReversibleOperations( $childDiff, $diffOp, $currentObject[$key] );

					if ( !$childDiff->isEmpty() ) {
						$diff->addOperations( array( $key => $childDiff ) );
					}
				}
			}
		}
	}

}