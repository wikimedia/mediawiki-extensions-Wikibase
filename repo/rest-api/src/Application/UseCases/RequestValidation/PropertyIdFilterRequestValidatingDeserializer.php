<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdFilterRequestValidatingDeserializer {
	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $validator ) {
		$this->propertyIdValidator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyIdFilterRequest $request ): ?NumericPropertyId {
		$filterPropertyId = $request->getPropertyIdFilter();
		if ( $filterPropertyId === null ) {
			return null;
		}

		$validationError = $this->propertyIdValidator->validate( $filterPropertyId );
		if ( $validationError ) {
			$context = $validationError->getContext();
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: {$context[PropertyIdValidator::CONTEXT_VALUE]}",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $context[PropertyIdValidator::CONTEXT_VALUE] ]
			);
		}
		return new NumericPropertyId( $filterPropertyId );
	}

}
