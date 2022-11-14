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
		if ( !isset( $serialization['parts'] ) ) {
			throw new MissingFieldException();
		}
		if ( !is_array( $serialization['parts'] ) || !$this->isArrayOfArrays( $serialization['parts'] ) ) {
			throw new InvalidFieldException();
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
