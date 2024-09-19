<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends UseCaseException {

	public const CANNOT_MODIFY_READ_ONLY_VALUE = 'cannot-modify-read-only-value';
	public const DATA_POLICY_VIOLATION = 'data-policy-violation';
	public const INVALID_KEY = 'invalid-key';
	public const INVALID_PATH_PARAMETER = 'invalid-path-parameter';
	public const INVALID_QUERY_PARAMETER = 'invalid-query-parameter';
	public const INVALID_VALUE = 'invalid-value';
	public const ITEM_REDIRECTED = 'redirected-item';
	public const ITEM_STATEMENT_ID_MISMATCH = 'item-statement-id-mismatch';
	public const MISSING_FIELD = 'missing-field';
	public const PATCH_RESULT_INVALID_KEY = 'patch-result-invalid-key';
	public const PATCH_RESULT_INVALID_VALUE = 'patch-result-invalid-value';
	public const PATCH_RESULT_MISSING_FIELD = 'patch-result-missing-field';
	public const PATCH_RESULT_MODIFIED_READ_ONLY_VALUE = 'patch-result-modified-read-only-value';
	public const PATCH_RESULT_REFERENCED_RESOURCE_NOT_FOUND = 'patch-result-referenced-resource-not-found';
	public const PATCH_RESULT_VALUE_TOO_LONG = 'patch-result-value-too-long';
	public const PATCH_TARGET_NOT_FOUND = 'patch-target-not-found';
	public const PATCH_TEST_FAILED = 'patch-test-failed';
	public const PATCHED_INVALID_SITELINK_TYPE = 'patched-invalid-sitelink-type';
	public const PATCHED_SITELINK_URL_NOT_MODIFIABLE = 'url-not-modifiable';
	public const PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH = 'patched-statement-group-property-id-mismatch';
	public const PERMISSION_DENIED = 'permission-denied';
	public const PERMISSION_DENIED_REASON_UNAUTHORIZED_BOT_EDIT = 'unauthorized-bot-edit';
	public const PERMISSION_DENIED_REASON_PAGE_PROTECTED = 'resource-protected';
	public const PERMISSION_DENIED_REASON_SPAM_BLACKLIST = 'spam-blacklist';
	public const PERMISSION_DENIED_REASON_USER_BLOCKED = 'blocked-user';
	public const PERMISSION_DENIED_REASON_ABUSE_FILTER = 'abuse-filter';
	public const PERMISSION_DENIED_UNKNOWN_REASON = 'permission-denied-unknown-reason';
	public const POLICY_VIOLATION_ITEM_LABEL_DESCRIPTION_DUPLICATE = 'item-label-description-duplicate';
	public const POLICY_VIOLATION_PROPERTY_LABEL_DUPLICATE = 'property-label-duplicate';
	public const POLICY_VIOLATION_LABEL_DESCRIPTION_SAME_VALUE = 'label-description-same-value';
	public const POLICY_VIOLATION_SITELINK_CONFLICT = 'sitelink-conflict';
	public const PROPERTY_STATEMENT_ID_MISMATCH = 'property-statement-id-mismatch';
	public const REFERENCED_RESOURCE_NOT_FOUND = 'referenced-resource-not-found';
	public const REQUEST_LIMIT_REACHED = 'request-limit-reached';
	public const REQUEST_LIMIT_REASON_RATE_LIMIT = 'rate-limit-reached';
	public const REQUEST_LIMIT_REASON_TEMP_ACCOUNT_CREATION_LIMIT = 'temp-account-creation-limit-reached';
	public const RESOURCE_NOT_FOUND = 'resource-not-found';
	public const RESOURCE_TOO_LARGE = 'resource-too-large';
	public const STATEMENT_GROUP_PROPERTY_ID_MISMATCH = 'statement-group-property-id-mismatch';
	public const UNEXPECTED_ERROR = 'unexpected-error';
	public const VALUE_TOO_LONG = 'value-too-long';

	public const CONTEXT_ACTUAL_VALUE = 'actual_value';
	public const CONTEXT_CONFLICTING_ITEM_ID = 'conflicting_item_id';
	public const CONTEXT_CONFLICTING_PROPERTY_ID = 'conflicting_property_id';
	public const CONTEXT_DENIAL_REASON = 'denial_reason';
	public const CONTEXT_DENIAL_CONTEXT = 'denial_context';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_FIELD = 'field';
	public const CONTEXT_ITEM_ID = 'item_id';
	public const CONTEXT_KEY = 'key';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LIMIT = 'limit';
	public const CONTEXT_PARAMETER = 'parameter';
	public const CONTEXT_PATH = 'path';
	public const CONTEXT_PROPERTY_ID = 'property_id';
	public const CONTEXT_REASON = 'reason';
	public const CONTEXT_REDIRECT_TARGET = 'redirect_target';
	public const CONTEXT_RESOURCE_TYPE = 'resource_type';
	public const CONTEXT_SITE_ID = 'site_id';
	public const CONTEXT_STATEMENT_GROUP_PROPERTY_ID = 'statement_group_property_id';
	public const CONTEXT_STATEMENT_ID = 'statement_id';
	public const CONTEXT_STATEMENT_PROPERTY_ID = 'statement_property_id';
	public const CONTEXT_TITLE = 'title';
	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_VIOLATION = 'violation';
	public const CONTEXT_VIOLATION_CONTEXT = 'violation_context';

	public const EXPECTED_CONTEXT_KEYS = [
		self::CANNOT_MODIFY_READ_ONLY_VALUE => [ self::CONTEXT_PATH ],
		self::DATA_POLICY_VIOLATION => [ self::CONTEXT_VIOLATION, self::CONTEXT_VIOLATION_CONTEXT ],
		self::INVALID_KEY => [ self::CONTEXT_PATH, self::CONTEXT_KEY ],
		self::INVALID_PATH_PARAMETER => [ self::CONTEXT_PARAMETER ],
		self::INVALID_QUERY_PARAMETER => [ self::CONTEXT_PARAMETER ],
		self::INVALID_VALUE => [ self::CONTEXT_PATH ],
		self::ITEM_REDIRECTED => [ self::CONTEXT_REDIRECT_TARGET ],
		self::ITEM_STATEMENT_ID_MISMATCH => [ self::CONTEXT_ITEM_ID, self::CONTEXT_STATEMENT_ID ],
		self::MISSING_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_FIELD ],
		self::PATCH_RESULT_INVALID_KEY => [ self::CONTEXT_PATH, self::CONTEXT_KEY ],
		self::PATCH_RESULT_INVALID_VALUE => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::PATCH_RESULT_MISSING_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_FIELD ],
		self::PATCH_RESULT_MODIFIED_READ_ONLY_VALUE => [ self::CONTEXT_PATH ],
		self::PATCH_RESULT_REFERENCED_RESOURCE_NOT_FOUND => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::PATCH_RESULT_VALUE_TOO_LONG => [ self::CONTEXT_PATH, self::CONTEXT_LIMIT ],
		self::PATCH_TARGET_NOT_FOUND => [ self::CONTEXT_PATH ],
		self::PATCH_TEST_FAILED => [ self::CONTEXT_PATH, self::CONTEXT_ACTUAL_VALUE ],
		self::PATCHED_INVALID_SITELINK_TYPE => [ self::CONTEXT_SITE_ID ],
		self::PATCHED_SITELINK_URL_NOT_MODIFIABLE => [ self::CONTEXT_SITE_ID ],
		self::PATCHED_STATEMENT_GROUP_PROPERTY_ID_MISMATCH => [
			self::CONTEXT_PATH,
			self::CONTEXT_STATEMENT_GROUP_PROPERTY_ID,
			self::CONTEXT_STATEMENT_PROPERTY_ID,
		],
		self::PERMISSION_DENIED => [ self::CONTEXT_DENIAL_REASON ],
		self::PERMISSION_DENIED_UNKNOWN_REASON => [],
		self::PROPERTY_STATEMENT_ID_MISMATCH => [ self::CONTEXT_PROPERTY_ID, self::CONTEXT_STATEMENT_ID ],
		self::REFERENCED_RESOURCE_NOT_FOUND => [ self::CONTEXT_PATH ],
		self::REQUEST_LIMIT_REACHED => [ self::CONTEXT_REASON ],
		self::RESOURCE_NOT_FOUND => [ self::CONTEXT_RESOURCE_TYPE ],
		self::RESOURCE_TOO_LARGE => [ self::CONTEXT_LIMIT ],
		self::STATEMENT_GROUP_PROPERTY_ID_MISMATCH => [
			self::CONTEXT_PATH,
			self::CONTEXT_STATEMENT_GROUP_PROPERTY_ID,
			self::CONTEXT_STATEMENT_PROPERTY_ID,
		],
		self::UNEXPECTED_ERROR => [],
		self::VALUE_TOO_LONG => [ self::CONTEXT_PATH, self::CONTEXT_LIMIT ],
	];

	private string $errorCode;
	private string $errorMessage;
	private array $errorContext;

	public function __construct( string $code, string $message, array $context = [] ) {
		parent::__construct();
		$this->errorCode = $code;
		$this->errorMessage = $message;
		$this->errorContext = $context;

		if ( !array_key_exists( $code, self::EXPECTED_CONTEXT_KEYS ) ) {
			throw new LogicException( "Unknown error code: '$code'" );
		}

		$contextKeys = array_keys( $context );
		$unexpectedContext = array_values( array_diff(
			$contextKeys,
			self::EXPECTED_CONTEXT_KEYS[$code]
		) );
		if ( $unexpectedContext ) {
			throw new LogicException( "Error context for '$code' should not contain keys: " . json_encode( $unexpectedContext ) );
		}
		$missingContext = array_values( array_diff( self::EXPECTED_CONTEXT_KEYS[$code], $contextKeys ) );
		if ( $missingContext ) {
			throw new LogicException( "Error context for '$code' should contain keys: " . json_encode( $missingContext ) );
		}
	}

	public static function newInvalidValue( string $path ): self {
		return new self( self::INVALID_VALUE, "Invalid value at '$path'", [ self::CONTEXT_PATH => $path ] );
	}

	/**
	 * @param string $path
	 * @param mixed $value
	 * @return self
	 */
	public static function newPatchResultInvalidValue( string $path, $value ): self {
		return new self(
			self::PATCH_RESULT_INVALID_VALUE,
			'Invalid value in patch result',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_VALUE => $value ]
		);
	}

	public static function newMissingField( string $path, string $field ): self {
		return new self(
			self::MISSING_FIELD,
			'Required field missing',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_FIELD => $field ]
		);
	}

	public static function newMissingFieldInPatchResult( string $path, string $field ): self {
		return new self(
			self::PATCH_RESULT_MISSING_FIELD,
			'Required field missing in patch result',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_FIELD => $field ]
		);
	}

	public static function newValueTooLong( string $path, int $maxLength, bool $isPatchRequest = false ): self {
		return new self(
			$isPatchRequest ? self::PATCH_RESULT_VALUE_TOO_LONG : self::VALUE_TOO_LONG,
			$isPatchRequest ? 'Patched value is too long' : 'The input value is too long',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_LIMIT => $maxLength ]
		);
	}

	public static function newInvalidKey( string $path, string $key ): self {
		return new self(
			self::INVALID_KEY,
			"Invalid key '$key' in '$path'",
			[ self::CONTEXT_PATH => $path, self::CONTEXT_KEY => $key ]
		);
	}

	public static function newPatchResultInvalidKey( string $path, string $key ): self {
		return new self(
			self::PATCH_RESULT_INVALID_KEY,
			'Invalid key in patch result',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_KEY => $key ]
		);
	}

	public static function newDataPolicyViolation( string $violationCode, array $violationContext ): self {
		return new self(
			self::DATA_POLICY_VIOLATION,
			'Edit violates data policy',
			[ self::CONTEXT_VIOLATION => $violationCode, self::CONTEXT_VIOLATION_CONTEXT => $violationContext ]
		);
	}

	public static function newPatchResultModifiedReadOnlyValue( string $path ): self {
		return new self(
			self::PATCH_RESULT_MODIFIED_READ_ONLY_VALUE,
			'Read only value in patch result cannot be modified',
			[ self::CONTEXT_PATH => $path ]
		);
	}

	public static function newPermissionDenied( string $reason, array $denialContext = [] ): self {
		$error = new self( self::PERMISSION_DENIED, 'Access to resource is denied', [ self::CONTEXT_DENIAL_REASON => $reason ] );
		if ( $denialContext ) {
			$error->errorContext[self::CONTEXT_DENIAL_CONTEXT] = $denialContext;
		}

		return $error;
	}

	public static function newRateLimitReached( string $reason ): self {
		return new self(
			self::REQUEST_LIMIT_REACHED,
			'Exceeded the limit of actions that can be performed in a given span of time',
			[ self::CONTEXT_REASON => $reason ]
		);
	}

	public static function newResourceNotFound( string $resourceType ): self {
		return new self(
			self::RESOURCE_NOT_FOUND,
			'The requested resource does not exist',
			[ self::CONTEXT_RESOURCE_TYPE => $resourceType ]
		);
	}

	public static function newPatchResultReferencedResourceNotFound( string $path, string $value ): self {
		return new self(
			self::PATCH_RESULT_REFERENCED_RESOURCE_NOT_FOUND,
			'The referenced resource does not exist',
			[ self::CONTEXT_PATH => $path, self::CONTEXT_VALUE => $value ]
		);
	}

	public static function newReferencedResourceNotFound( string $path ): self {
		return new self(
			self::REFERENCED_RESOURCE_NOT_FOUND,
			'The referenced resource does not exist',
			[ self::CONTEXT_PATH => $path ]
		);
	}

	public function getErrorCode(): string {
		return $this->errorCode;
	}

	public function getErrorMessage(): string {
		return $this->errorMessage;
	}

	public function getErrorContext(): array {
		return $this->errorContext;
	}

}
