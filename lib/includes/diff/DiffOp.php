<?php

namespace Wikibase;
use MWException;

interface IDiffOp {

	public function getType();

	//public function toArray();

}

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