<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\Repo\RestApi\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
interface JsonPatchValidator {

	public const CODE_INVALID = 'patch-invalid';
	public const CODE_INVALID_FIELD_TYPE = 'patch-invalid-field-type';
	public const CODE_MISSING_FIELD = 'patch-missing-field';
	public const CODE_INVALID_OPERATION = 'patch-invalid-op';

	public const ERROR_CONTEXT_OPERATION = 'operation';
	public const ERROR_CONTEXT_FIELD = 'field';

	public function validate( array $patch ): ?ValidationError;

}
