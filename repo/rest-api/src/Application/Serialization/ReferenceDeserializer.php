<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Reference;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;

/**
 * @license GPL-2.0-or-later
 */
class ReferenceDeserializer {

	private PropertyValuePairDeserializer $propertyValuePairDeserializer;

	public function __construct( PropertyValuePairDeserializer $propertyValuePairDeserializer ) {
		$this->propertyValuePairDeserializer = $propertyValuePairDeserializer;
	}

	/**
	 * @throws MissingFieldException
	 * @throws InvalidFieldException
	 */
	public function deserialize( array $serialization, string $basePath = '' ): Reference {
		if ( !array_key_exists( 'parts', $serialization ) ) {
			throw new MissingFieldException( 'parts', $basePath );
		}
		if ( !is_array( $serialization['parts'] ) || !$this->isArrayOfArrays( $serialization['parts'] ) ) {
			throw new InvalidFieldException( 'parts', $serialization['parts'], "$basePath/parts" );
		}

		return new Reference( array_map(
			fn( $i, array $part ) => $this->propertyValuePairDeserializer->deserialize( $part, "$basePath/parts/$i" ),
			array_keys( $serialization['parts'] ),
			$serialization['parts'],
		) );
	}

	private function isArrayOfArrays( array $list ): bool {
		return array_reduce( $list, fn( bool $isValid, $item ) => $isValid && is_array( $item ), true );
	}

}
