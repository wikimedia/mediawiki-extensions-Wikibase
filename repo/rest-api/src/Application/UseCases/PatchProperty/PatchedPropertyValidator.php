<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\DataModel\Entity\Property;
use Wikibase\Repo\RestApi\Application\Serialization\PropertyDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidator {

	private PropertyDeserializer $propertyDeserializer;

	public function __construct( PropertyDeserializer $propertyDeserializer ) {
		$this->propertyDeserializer = $propertyDeserializer;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $serialization, Property $originalProperty ): Property {
		if ( !isset( $serialization['id'] ) ) { // ignore ID removal
			$serialization['id'] = $originalProperty->getId()->getSerialization();
		}

		$this->assertNoMissingMandatoryFields( $serialization );
		$this->assertNoIllegalModification( $serialization, $originalProperty );
		$this->assertNoUnexpectedFields( $serialization );
		$this->assertValidFields( $serialization );

		return $this->propertyDeserializer->deserialize( $serialization );
	}

	private function assertNoMissingMandatoryFields( array $serialization ): void {
		if ( !isset( $serialization['data-type'] ) ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_MISSING_FIELD,
				"Mandatory field missing in the patched property: 'data-type'",
				[ UseCaseError::CONTEXT_PATH => 'data-type' ]
			);
		}
	}

	private function assertNoUnexpectedFields( array $serialization ): void {
		$expectedFields = [ 'id', 'data-type', 'type', 'labels', 'descriptions', 'aliases', 'statements' ];

		foreach ( array_keys( $serialization ) as $field ) {
			if ( !in_array( $field, $expectedFields ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_UNEXPECTED_FIELD,
					"The patched property contains an unexpected field: '$field'"
				);
			}
		}
	}

	private function assertValidFields( array $serialization ): void {
		// 'id' and 'data-type' are not modifiable and 'type' is ignored, so we only check the expected array fields
		foreach ( [ 'labels', 'descriptions', 'aliases', 'statements' ] as $field ) {
			if ( isset( $serialization[$field] ) && !is_array( $serialization[$field] ) ) {
				throw new UseCaseError(
					UseCaseError::PATCHED_PROPERTY_INVALID_FIELD,
					"Invalid input for '$field' in the patched property",
					[
						UseCaseError::CONTEXT_PATH => $field,
						UseCaseError::CONTEXT_VALUE => $serialization[$field],
					]
				);
			}
		}
	}

	private function assertNoIllegalModification( array $serialization, Property $originalProperty ): void {
		if ( $serialization['id'] !== $originalProperty->getId()->getSerialization() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_ID,
				'Cannot change the ID of the existing property'
			);
		}

		if ( $serialization['data-type'] !== $originalProperty->getDataTypeId() ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_PROPERTY_INVALID_OPERATION_CHANGE_PROPERTY_DATATYPE,
				'Cannot change the datatype of the existing property'
			);
		}
	}
}
