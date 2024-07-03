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
					throw new UseCaseError( UseCaseError::INVALID_PATCH, 'The provided patch is invalid' );

				case JsonPatchValidator::CODE_INVALID_OPERATION:
					$opIndex = array_search( $context[JsonPatchValidator::CONTEXT_OPERATION], $request->getPatch() );
					if ( !is_int( $opIndex ) ) {
						throw new LogicException( "The invalid operation wasn't found in the original patch document" );
					}

					throw UseCaseError::newInvalidValue( "/patch/$opIndex/op" );

				case JsonPatchValidator::CODE_INVALID_FIELD_TYPE:
					throw new UseCaseError(
						UseCaseError::INVALID_PATCH_FIELD_TYPE,
						"The value of '{$context[JsonPatchValidator::CONTEXT_FIELD]}' must be of type string",
						[
							UseCaseError::CONTEXT_OPERATION => $context[JsonPatchValidator::CONTEXT_OPERATION],
							UseCaseError::CONTEXT_FIELD => $context[JsonPatchValidator::CONTEXT_FIELD],
						]
					);

				case JsonPatchValidator::CODE_MISSING_FIELD:
					throw new UseCaseError(
						UseCaseError::MISSING_JSON_PATCH_FIELD,
						"Missing '{$context[JsonPatchValidator::CONTEXT_FIELD]}' in JSON patch",
						[
							UseCaseError::CONTEXT_OPERATION => $context[JsonPatchValidator::CONTEXT_OPERATION],
							UseCaseError::CONTEXT_FIELD => $context[JsonPatchValidator::CONTEXT_FIELD],
						]
					);

				default:
					throw new LogicException( "Unknown validation error code: {$validationError->getCode()}" );
			}
		}

		return $request->getPatch();
	}

}
