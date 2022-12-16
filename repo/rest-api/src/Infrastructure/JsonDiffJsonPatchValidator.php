<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Swaggest\JsonDiff\Exception;
use Swaggest\JsonDiff\InvalidFieldTypeException;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\MissingFieldException;
use Swaggest\JsonDiff\UnknownOperationException;
use Wikibase\Repo\RestApi\Validation\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidator implements JsonPatchValidator {

	public function validate( array $patch ): ?ValidationError {
		try {
			JsonPatch::import( $patch );
		} catch ( MissingFieldException $e ) {
			return new ValidationError(
				self::CODE_MISSING_FIELD,
				[ self::CONTEXT_OPERATION => (array)$e->getOperation(), self::CONTEXT_FIELD => $e->getMissingField() ]
			);
		} catch ( InvalidFieldTypeException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD_TYPE,
				[ self::CONTEXT_OPERATION => (array)$e->getOperation(), self::CONTEXT_FIELD => $e->getField() ]
			);
		} catch ( UnknownOperationException $e ) {
			return new ValidationError(
				self::CODE_INVALID_OPERATION,
				[ self::CONTEXT_OPERATION => (array)$e->getOperation() ]
			);
		} catch ( Exception $e ) {
			return new ValidationError( self::CODE_INVALID );
		}

		return null;
	}

}
