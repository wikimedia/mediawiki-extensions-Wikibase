<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Swaggest\JsonDiff\Exception;
use Swaggest\JsonDiff\JsonPatch;
use Wikibase\Repo\RestApi\Domain\Services\JsonPatchValidator;
use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class JsonDiffJsonPatchValidator implements JsonPatchValidator {

	public function validate( array $patch, string $source ): ?ValidationError {
		try {
			JsonPatch::import( $patch );
		} catch ( Exception $e ) {
			return new ValidationError( '', $source );
		}

		return null;
	}

}
