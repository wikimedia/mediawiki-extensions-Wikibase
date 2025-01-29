<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\PropertyIdValidator;

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
			throw new UseCaseError(
				UseCaseError::INVALID_PATH_PARAMETER,
				"Invalid path parameter: 'property_id'",
				[ UseCaseError::CONTEXT_PARAMETER => 'property_id' ]
			);
		}
		return new NumericPropertyId( $propertyId );
	}

}
