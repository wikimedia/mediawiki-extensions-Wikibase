<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Serialization;

use Wikibase\DataModel\Reference;

/**
 * @license GPL-2.0-or-later
 */
class ReferenceDeserializer {

	private PropertyValuePairDeserializer $propertyValuePairDeserializer;

	public function __construct( PropertyValuePairDeserializer $propertyValuePairDeserializer ) {
		$this->propertyValuePairDeserializer = $propertyValuePairDeserializer;
	}

	public function deserialize( array $serialization ): Reference {
		if ( !array_key_exists( 'parts', $serialization ) ) {
			throw new MissingFieldException( 'parts' );
		}
		if ( !is_array( $serialization['parts'] ) || !$this->isArrayOfArrays( $serialization['parts'] ) ) {
			throw new InvalidFieldException( 'parts', $serialization['parts'] );
		}

		return new Reference( array_map(
			fn( array $part ) => $this->propertyValuePairDeserializer->deserialize( $part ),
			$serialization['parts']
		) );
	}

	private function isArrayOfArrays( array $list ): bool {
		return array_reduce( $list, fn( bool $isValid, $item ) => $isValid && is_array( $item ), true );
	}

}
