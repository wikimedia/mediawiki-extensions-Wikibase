<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Swaggest\JsonDiff\Exception;
use Swaggest\JsonDiff\JsonPatch;
use Swaggest\JsonDiff\MissingFieldException;
use Swaggest\JsonDiff\UnknownOperationException;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\PatchInvalidOpValidationError;
use Wikibase\Repo\RestApi\Validation\PatchMissingFieldValidationError;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidator implements JsonPatchValidator {

	public function validate( array $patch, string $source ): ?ValidationError {
		try {
			JsonPatch::import( $patch );
		} catch ( MissingFieldException $e ) {
			return new PatchMissingFieldValidationError(
				$e->getMissingField(),
				$source,
				[ 'operation' => (array)$e->getOperation() ]
			);
		} catch ( UnknownOperationException $e ) {
			return new PatchInvalidOpValidationError(
				$e->getOperation()->op,
				$source,
				[ 'operation' => (array)$e->getOperation() ]
			);
		} catch ( Exception $e ) {
			return new ValidationError( '', $source );
		}

		return null;
	}

}
