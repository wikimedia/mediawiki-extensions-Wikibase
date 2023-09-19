<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdValidatingDeserializer {
	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $validator ) {
		$this->propertyIdValidator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( string $propertyId ): NumericPropertyId {
		$validationError = $this->propertyIdValidator->validate( $propertyId );
		if ( $validationError ) {
			$invalidPropertyId = $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE];
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: $invalidPropertyId",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $invalidPropertyId ]
			);
		}
		return new NumericPropertyId( $propertyId );
	}

}
