<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use ArrayObject;

/**
 * @license GPL-2.0-or-later
 */
class ConvertArrayObjectsToArray {

	public static function execute( iterable $serialization ): array {
		$output = [];
		foreach ( $serialization as $key => $value ) {
			if ( is_array( $value ) ) {
				$output[ $key ] = self::execute( $value );
			} elseif ( $value instanceof ArrayObject ) {
				$output[ $key ] = self::execute( (array)$value );
			} else {
				$output[ $key ] = $value;
			}
		}
		return $output;
	}

}
