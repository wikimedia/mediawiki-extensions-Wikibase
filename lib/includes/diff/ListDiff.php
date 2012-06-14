<?php

namespace Wikibase;

/**
 * Class representing the diff between to (non-associative) arrays.
 * Since items are not identified by keys, we only deal with the actual values,
 * so can only compute additions and removals.
 *
 * TODO: currently not figured out how duplicate entries should be treated.
 * Using native array_diff behaviour for now, but might not be what we want.
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
class ListDiff extends Diff implements IDiffOp {

	public function getType() {
		return 'list';
	}

	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
	);

	/**
	 * Creates and returns an empty ListDiff.
	 *
	 * @since 0.1
	 *
	 * @return ListDiff
	 */
	public static function newEmpty() {
		return new static( array() );
	}

	/**
	 * Creates a new ListDiff given two arrays.
	 *
	 * @since 0.1
	 *
	 * @param array $firstList
	 * @param array $secondList
	 *
	 * @return ListDiff
	 */
	public static function newFromArrays( array $firstList, array $secondList ) {
		$operations = array();

		foreach ( array_diff( $secondList, $firstList ) as $addition ) {
			$operations[] = new DiffOpAdd( $addition );
		}

		foreach ( array_diff( $firstList, $secondList ) as $removal ) {
			$operations[] = new DiffOpRemove( $removal );
		}

		return new static( $operations );
	}

}