<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
class FieldsFilterValidatingDeserializer {

	public const FIELDS_QUERY_PARAM = '_fields';
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
				throw new UseCaseError(
					UseCaseError::INVALID_QUERY_PARAMETER,
					"Invalid query parameter: '" . self::FIELDS_QUERY_PARAM . "'",
					[ UseCaseError::CONTEXT_PARAMETER => self::FIELDS_QUERY_PARAM ]
				);
			}
		}

		return $fields;
	}

}
