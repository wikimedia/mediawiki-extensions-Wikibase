<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetProperty;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;
use Wikibase\Repo\RestApi\Domain\ReadModel\PropertyParts;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyValidator {

	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $propertyIdValidator ) {
		$this->propertyIdValidator = $propertyIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetPropertyRequest $request ): void {
		$validationError = $this->propertyIdValidator->validate( $request->getPropertyId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE]}",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE] ]
			);
		}

		$this->validateFields( $request->getFields() );
	}

	/**
	 * @throws UseCaseError
	 */
	private function validateFields( array $fields ): void {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, PropertyParts::VALID_FIELDS ) ) {
				throw new UseCaseError( UseCaseError::INVALID_FIELD, "Not a valid field: $field" );
			}
		}
	}

}
