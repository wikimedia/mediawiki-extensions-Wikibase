<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface JsonPatchValidator {

	public const CODE_INVALID = 'patch-invalid';
	public const CODE_INVALID_FIELD_TYPE = 'patch-invalid-field-type';
	public const CODE_MISSING_FIELD = 'patch-missing-field';
	public const CODE_INVALID_OPERATION = 'patch-invalid-op';

	public const CONTEXT_OPERATION = 'operation';
	public const CONTEXT_FIELD = 'field';

	public function validate( array $patch ): ?ValidationError;

}
