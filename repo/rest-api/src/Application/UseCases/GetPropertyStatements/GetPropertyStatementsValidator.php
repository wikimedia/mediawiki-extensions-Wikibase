<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsValidator {

	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $propertyIdValidator ) {
		$this->propertyIdValidator = $propertyIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetPropertyStatementsRequest $request ): void {
		$propertyIdsToValidate = [ $request->getSubjectPropertyId() ];

		if ( $request->getFilterPropertyId() ) {
			$propertyIdsToValidate[] = $request->getFilterPropertyId();
		}

		foreach ( $propertyIdsToValidate as $propertyId ) {
			$validationError = $this->propertyIdValidator->validate( $propertyId );
			if ( $validationError ) {
				$context = $validationError->getContext();
				throw new UseCaseError(
					UseCaseError::INVALID_PROPERTY_ID,
					"Not a valid property ID: {$context[PropertyIdValidator::CONTEXT_VALUE]}",
					$context
				);
			}
		}
	}
}
