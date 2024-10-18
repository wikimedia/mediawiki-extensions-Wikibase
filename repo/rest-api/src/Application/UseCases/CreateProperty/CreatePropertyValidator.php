<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidator {

	private PropertyDeserializer $propertyDeserializer;

	public function __construct( PropertyDeserializer $propertyDeserializer	) {
		$this->propertyDeserializer = $propertyDeserializer;
	}

	public function validateAndDeserialize( CreatePropertyRequest $request ): DeserializedCreatePropertyRequest {
		return new DeserializedCreatePropertyRequest( $this->propertyDeserializer->deserialize( $request->getProperty() ) );
	}
}
