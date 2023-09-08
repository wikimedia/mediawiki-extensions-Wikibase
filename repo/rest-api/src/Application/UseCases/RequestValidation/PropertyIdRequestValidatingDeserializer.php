<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\PropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdRequestValidatingDeserializer {
	public const DESERIALIZED_VALUE = 'property-id';

	private PropertyIdValidator $propertyIdValidator;

	public function __construct( PropertyIdValidator $validator ) {
		$this->propertyIdValidator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PropertyIdRequest $request ): array {
		$validationError = $this->propertyIdValidator->validate( $request->getPropertyId() );
		if ( $validationError ) {
			$invalidPropertyId = $validationError->getContext()[PropertyIdValidator::CONTEXT_VALUE];
			throw new UseCaseError(
				UseCaseError::INVALID_PROPERTY_ID,
				"Not a valid property ID: $invalidPropertyId",
				[ UseCaseError::CONTEXT_PROPERTY_ID => $invalidPropertyId ]
			);
		}
		return [ self::DESERIALIZED_VALUE => new NumericPropertyId( $request->getPropertyId() ) ];
	}

}
