<?php

namespace Wikibase;

/**
 * Class representing the diff between to (non-associative) arrays.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ListDiff extends DiffOpList implements IDiffOp {

	public function getType() {
		return 'list';
	}

	protected $typePointers = array(
		'add' => array(),
		'remove' => array(),
	);

	public static function newEmpty() {
		return new static( array() );
	}

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