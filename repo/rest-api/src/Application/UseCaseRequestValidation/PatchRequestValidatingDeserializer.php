<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use LogicException;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\JsonPatchValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchRequestValidatingDeserializer {

	private JsonPatchValidator $validator;

	public function __construct( JsonPatchValidator $validator ) {
		$this->validator = $validator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchRequest $request ): array {
		$validationError = $this->validator->validate( $request->getPatch() );
		if ( $validationError ) {
			$context = $validationError->getContext();
			switch ( $validationError->getCode() ) {
				case JsonPatchValidator::CODE_INVALID:
					throw UseCaseError::newInvalidValue( '/patch' );

				case JsonPatchValidator::CODE_INVALID_OPERATION:
					$opIndex = array_search( $context[JsonPatchValidator::CONTEXT_OPERATION], $request->getPatch() );
					if ( !is_int( $opIndex ) ) {
						throw new LogicException( "The invalid operation wasn't found in the original patch document" );
					}

					throw UseCaseError::newInvalidValue( "/patch/$opIndex/op" );

				case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
					$opField = $context[JsonPatchValidator::CONTEXT_FIELD];
					$opIndex = array_search( $context[JsonPatchValidator::CONTEXT_OPERATION], $request->getPatch() );
					if ( !is_int( $opIndex ) ) {
						throw new LogicException( "The invalid operation wasn't found in the original patch document" );
					}

					throw UseCaseError::newInvalidValue( "/patch/$opIndex/$opField" );

				case JsonPatchValidator::CODE_MISSING_FIELD:
					$opField = $context[JsonPatchValidator::CONTEXT_FIELD];
					$opIndex = array_search( $context[JsonPatchValidator::CONTEXT_OPERATION], $request->getPatch() );
					if ( !is_int( $opIndex ) ) {
						throw new LogicException( "The invalid operation wasn't found in the original patch document" );
					}
					throw UseCaseError::newMissingField( "/patch/$opIndex", $opField );

				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $request->getPatch();
	}

}
