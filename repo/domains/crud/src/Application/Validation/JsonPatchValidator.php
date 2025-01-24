<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface JsonPatchValidator {

	public const CODE_INVALID = 'json-patch-validator-code-invalid';
	public const CODE_INVALID_FIELD_TYPE = 'json-patch-validator-code-invalid-field-type';
	public const CODE_MISSING_FIELD = 'json-patch-validator-code-missing-field';
	public const CODE_INVALID_OPERATION = 'json-patch-validator-code-invalid-op';

	public const CONTEXT_OPERATION = 'json-patch-validator-context-operation';
	public const CONTEXT_FIELD = 'json-patch-validator-context-field';

	public function validate( array $patch ): ?ValidationError;

}
