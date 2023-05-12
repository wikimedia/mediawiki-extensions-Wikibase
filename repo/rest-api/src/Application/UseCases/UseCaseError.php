<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends UseCaseException {

	public const COMMENT_TOO_LONG = 'comment-too-long';
	public const INVALID_EDIT_TAG = 'invalid-edit-tag';
	public const INVALID_FIELD = 'invalid-field';
	public const INVALID_ITEM_ID = 'invalid-item-id';
	public const INVALID_PROPERTY_ID = 'invalid-property-id';
	public const INVALID_STATEMENT_ID = 'invalid-statement-id';
	public const INVALID_LABEL = 'invalid-label';
	public const PATCHED_LABEL_INVALID = 'patched-label-invalid';
	public const INVALID_LANGUAGE_CODE = 'invalid-language-code';
	public const PATCHED_LABEL_INVALID_LANGUAGE_CODE = 'patched-labels-invalid-language-code';
	public const LABEL_EMPTY = 'label-empty';
	public const LABEL_TOO_LONG = 'label-too-long';
	public const PATCHED_LABEL_EMPTY = 'patched-label-empty';
	public const PATCHED_LABEL_TOO_LONG = 'patched-label-too-long';
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
	public const ITEM_LABEL_DESCRIPTION_DUPLICATE = 'item-label-description-duplicate';
	public const LABEL_NOT_DEFINED = 'label-not-defined';
	public const LABEL_DESCRIPTION_SAME_VALUE = 'label-description-same-value';
	public const ALIASES_NOT_DEFINED = 'aliases-not-defined';
	public const DESCRIPTION_NOT_DEFINED = 'description-not-defined';
	public const DESCRIPTION_EMPTY = 'description-empty';
	public const DESCRIPTION_TOO_LONG = 'description-too-long';
	public const INVALID_DESCRIPTION = 'invalid-description';
	public const ITEM_REDIRECTED = 'redirected-item';
	public const PERMISSION_DENIED = 'permission-denied';
	public const STATEMENT_DATA_INVALID_FIELD = 'statement-data-invalid-field';
	public const STATEMENT_DATA_MISSING_FIELD = 'statement-data-missing-field';
	public const STATEMENT_NOT_FOUND = 'statement-not-found';
	public const UNEXPECTED_ERROR = 'unexpected-error';

	private string $errorCode;
	private string $errorMessage;
	private ?array $errorContext;

	public function __construct( string $code, string $message, array $context = null ) {
		parent::__construct();
		$this->errorCode = $code;
		$this->errorMessage = $message;
		$this->errorContext = $context;
	}

	public function getErrorCode(): string {
		return $this->errorCode;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	public function getErrorContext(): ?array {
		return $this->errorContext;
	}
}
