<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateProperty;

use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\EditMetadataRequestValidatingDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class CreatePropertyValidator {

	private PropertyDeserializer $propertyDeserializer;
	private EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer;
	private array $dataTypesArray;

	public function __construct(
		PropertyDeserializer $propertyDeserializer,
		EditMetadataRequestValidatingDeserializer $editMetadataRequestValidatingDeserializer,
		array $dataTypesArray
	) {
		$this->propertyDeserializer = $propertyDeserializer;
		$this->editMetadataRequestValidatingDeserializer = $editMetadataRequestValidatingDeserializer;
		$this->dataTypesArray = $dataTypesArray;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreatePropertyRequest $request ): DeserializedCreatePropertyRequest {
		$propertySerialization = $request->getProperty();
		if ( !isset( $propertySerialization['data_type'] ) ) {
			throw UseCaseError::newMissingField( '/property', 'data_type' );
		}

		$this->validateTopLevelFields( $propertySerialization );

		return new DeserializedCreatePropertyRequest(
			$this->propertyDeserializer->deserialize( $request->getProperty() ),
			$this->editMetadataRequestValidatingDeserializer->validateAndDeserialize( $request )
		);
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateTopLevelFields( array $property ): void {
		if (
			!is_string( $property['data_type'] ) ||
			!in_array( $property['data_type'], $this->dataTypesArray )
		) {
			throw UseCaseError::newInvalidValue( '/property/data_type' );
		}

		foreach ( [ 'labels', 'descriptions', 'aliases', 'statements' ] as $arrayField ) {
			if ( isset( $property[$arrayField] ) && !is_array( $property[$arrayField] ) ) {
				throw UseCaseError::newInvalidValue( "/property/$arrayField" );
			}
		}
	}
}
