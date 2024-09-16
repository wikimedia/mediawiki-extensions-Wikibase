<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Reference;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\MissingFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\PropertyNotFoundException;

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
	 * @throws PropertyNotFoundException
	 */
	public function deserialize( array $serialization, string $basePath = '' ): Reference {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			throw new InvalidFieldException( '', $serialization, $basePath );
		}

		if ( !array_key_exists( 'parts', $serialization ) ) {
			throw new MissingFieldException( 'parts', $basePath );
		}

		if ( !is_array( $serialization['parts'] ) || !array_is_list( $serialization['parts'] ) ) {
			throw new InvalidFieldException( 'parts', $serialization['parts'], "$basePath/parts" );
		}

		$parts = [];
		foreach ( $serialization['parts'] as $index => $part ) {
			if ( !is_array( $part ) ) {
				throw new InvalidFieldException( "$index", $part, "$basePath/parts/$index" );
			}

			$parts[] = $this->propertyValuePairDeserializer->deserialize( $part, "$basePath/parts/$index" );
		}

		return new Reference( $parts );
	}

}
