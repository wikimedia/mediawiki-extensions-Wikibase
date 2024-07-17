<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class Utils {

	/**
	 * @param mixed $value
	 * @param array $serialization
	 *
	 * @return int
	 * @throws LogicException if $value is not found in $serialization
	 */
	public static function getIndexOfValueInSerialization( $value, array $serialization ): int {
		$index = array_search( $value, $serialization );
		if ( !is_int( $index ) ) {
			throw new LogicException( 'Could not find value in serialization' );
		}
		return $index;
	}

}
