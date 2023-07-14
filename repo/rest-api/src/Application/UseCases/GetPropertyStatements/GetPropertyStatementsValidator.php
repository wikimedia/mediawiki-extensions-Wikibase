<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsValidator {

	public const CONTEXT_PROPERTY_ID = 'property-id';

	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $propertyIdValidator ) {
		$this->propertyIdValidator = $propertyIdValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function assertValidRequest( GetPropertyStatementsRequest $request ): void {
		$this->assertValidPropertyId( $request->getPropertyId() );

		if ( $request->getFilterPropertyId() ) {
			// @phan-suppress-next-line PhanTypeMismatchArgumentNullable
			$this->assertValidPropertyId( $request->getFilterPropertyId() );
		}
	}

	/**
	 * @throws UseCaseError
	 */
	private function assertValidPropertyId( string $propertyId ): void {
		$validationError = $this->propertyIdValidator->validate( $propertyId );
		if ( $validationError ) {
			$invalidPropertyId = $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE];
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: $invalidPropertyId",
				[ self::CONTEXT_PROPERTY_ID => $invalidPropertyId ]
			);
		}
	}

}
