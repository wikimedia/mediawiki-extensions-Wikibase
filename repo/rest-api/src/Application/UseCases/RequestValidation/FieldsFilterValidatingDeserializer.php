<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class FieldsFilterValidatingDeserializer {

	private array $validFields;

	public function __construct( array $validFields ) {
		$this->validFields = $validFields;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $fields ): array {
		foreach ( $fields as $field ) {
			if ( !in_array( $field, $this->validFields ) ) {
				throw new UseCaseError( UseCaseError::INVALID_FIELD, "Not a valid field: $field" );
			}
		}

		return $fields;
	}

}
