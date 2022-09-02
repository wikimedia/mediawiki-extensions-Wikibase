<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
interface JsonPatchValidator {

	public function validate( array $patch, string $source ): ?ValidationError;

}
