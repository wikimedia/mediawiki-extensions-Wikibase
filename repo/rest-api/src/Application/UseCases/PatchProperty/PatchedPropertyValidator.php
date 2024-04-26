<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\Repo\RestApi\Application\Serialization\AliasesDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\StatementsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class PatchedPropertyValidator {

	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;
	private AliasesDeserializer $aliasesDeserializer;
	private StatementsDeserializer $statementsDeserializer;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer,
		AliasesDeserializer $aliasesDeserializer,
		StatementsDeserializer $statementsDeserializer
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->aliasesDeserializer = $aliasesDeserializer;
		$this->statementsDeserializer = $statementsDeserializer;
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

		return new Property(
			new NumericPropertyId( $serialization[ 'id' ] ),
			new Fingerprint(
				$this->labelsDeserializer->deserialize( (array)( $serialization[ 'labels' ] ?? [] ) ),
				$this->descriptionsDeserializer->deserialize( (array)( $serialization[ 'descriptions' ] ?? [] ) ),
				$this->aliasesDeserializer->deserialize( (array)( $serialization[ 'aliases' ] ?? [] ) )
			),
			$serialization[ 'data-type' ],
			$this->statementsDeserializer->deserialize( (array)( $serialization[ 'statements' ] ?? [] ) )
		);
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
