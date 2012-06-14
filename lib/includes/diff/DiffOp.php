<?php

namespace Wikibase;
use MWException;

/**
 * Interface for diff operations. A diff operation
 * represents a change to a single element.
 * In case the elements are maps or diffs, the resulting operation
 * can be a MapDiff or ListDiff, which contain their own list of IDiffOp objects.
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
interface IDiffOp {

	public function getType();

	//public function toArray();

}

/**
 * Base class for diff operations. A diff operation
 * represents a change to a single element.
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
abstract class DiffOp implements IDiffOp {

	/**
	 * Returns a new IDiffOp implementing instance to represent the provided change.
	 *
	 * @since 0.1
	 *
	 * @param array $array
	 *
	 * @return DiffOpAdd|DiffOpChange|DiffOpRemove
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