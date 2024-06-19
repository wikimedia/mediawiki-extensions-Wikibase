<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\PropertyIdValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyIdFilterValidatingDeserializer {

	private const PROPERTY_FILTER_QUERY_PARAM = 'property';
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
			throw new UseCaseError(
				UseCaseError::INVALID_QUERY_PARAMETER,
				"Invalid query parameter: '" . self::PROPERTY_FILTER_QUERY_PARAM . "'",
				[ UseCaseError::CONTEXT_PARAMETER => self::PROPERTY_FILTER_QUERY_PARAM ]
			);
		}
		return new NumericPropertyId( $propertyId );
	}

}
