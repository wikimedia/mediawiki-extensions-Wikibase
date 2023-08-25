<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class ReplacePropertyStatementValidator {

	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $propertyIdValidator ) {
		$this->propertyIdValidator = $propertyIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( ReplacePropertyStatementRequest $request ): void {
		$validationError = $this->propertyIdValidator->validate( $request->getPropertyId() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE]}",
				[ 'property-id' => $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE] ]
			);
		}
	}

}
