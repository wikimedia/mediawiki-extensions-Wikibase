<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class ErrorResponse {
	public const COMMENT_TOO_LONG = 'comment-too-long';
	public const INVALID_EDIT_TAG = 'invalid-edit-tag';
	public const INVALID_FIELD = 'invalid-field';
	public const INVALID_ITEM_ID = 'invalid-item-id';
	public const INVALID_PROPERTY_ID = 'invalid-property-id';
	public const INVALID_STATEMENT_ID = 'invalid-statement-id';
	public const INVALID_OPERATION_CHANGED_STATEMENT_ID = 'invalid-operation-change-statement-id';
	public const INVALID_OPERATION_CHANGED_PROPERTY = 'invalid-operation-change-property-of-statement';
	public const PATCHED_STATEMENT_INVALID_FIELD = 'patched-statement-invalid-field';
	public const PATCHED_STATEMENT_MISSING_FIELD = 'patched-statement-missing-field';
	public const PATCH_TARGET_NOT_FOUND = 'patch-target-not-found';
	public const PATCH_TEST_FAILED = 'patch-test-failed';
	public const INVALID_PATCH = 'invalid-patch';
	public const INVALID_PATCH_OPERATION = 'invalid-patch-operation';
	public const INVALID_PATCH_FIELD_TYPE = 'invalid-patch-field-type';
	public const MISSING_JSON_PATCH_FIELD = 'missing-json-patch-field';
	public const ITEM_NOT_FOUND = 'item-not-found';
	public const ITEM_REDIRECTED = 'redirected-item';
	public const PERMISSION_DENIED = 'permission-denied';
	public const STATEMENT_DATA_INVALID_FIELD = 'statement-data-invalid-field';
	public const STATEMENT_DATA_MISSING_FIELD = 'statement-data-missing-field';
	public const STATEMENT_NOT_FOUND = 'statement-not-found';
	public const UNEXPECTED_ERROR = 'unexpected-error';

	private string $code;
	private string $message;
	private ?array $context;

	public function __construct( string $code, string $message, array $context = null ) {
		$this->code = $code;
		$this->message = $message;
		$this->context = $context;
	}

	public function getCode(): string {
		return $this->code;
	}

	public function getMessage(): string {
		return $this->message;
	}

	public function getContext(): ?array {
		return $this->context;
	}
}
