<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use LogicException;

/**
 * @license GPL-2.0-or-later
 */
class UseCaseError extends UseCaseException {

	public const ALIASES_NOT_DEFINED = 'aliases-not-defined';
	public const ALIAS_EMPTY = 'alias-empty';
	public const ALIAS_LIST_EMPTY = 'alias-list-empty';
	public const ALIAS_TOO_LONG = 'alias-too-long';
	public const ALIAS_DUPLICATE = 'duplicate-alias';
	public const INVALID_ALIAS = 'invalid-alias';
	public const COMMENT_TOO_LONG = 'comment-too-long';
	public const DESCRIPTION_EMPTY = 'description-empty';
	public const DESCRIPTION_NOT_DEFINED = 'description-not-defined';
	public const DESCRIPTION_TOO_LONG = 'description-too-long';
	public const INVALID_DESCRIPTION = 'invalid-description';
	public const INVALID_EDIT_TAG = 'invalid-edit-tag';
	public const INVALID_FIELD = 'invalid-field';
	public const INVALID_ITEM_ID = 'invalid-item-id';
	public const INVALID_LABEL = 'invalid-label';
	public const INVALID_LANGUAGE_CODE = 'invalid-language-code';
	public const INVALID_OPERATION_CHANGED_PROPERTY = 'invalid-operation-change-property-of-statement';
	public const INVALID_OPERATION_CHANGED_STATEMENT_ID = 'invalid-operation-change-statement-id';
	public const INVALID_PATCH = 'invalid-patch';
	public const INVALID_PATCH_FIELD_TYPE = 'invalid-patch-field-type';
	public const INVALID_PATCH_OPERATION = 'invalid-patch-operation';
	public const INVALID_PROPERTY_ID = 'invalid-property-id';
	public const INVALID_SITE_ID = 'invalid-site-id';
	public const INVALID_STATEMENT_ID = 'invalid-statement-id';
	public const INVALID_STATEMENT_SUBJECT_ID = 'invalid-statement-subject-id';
	public const ITEM_LABEL_DESCRIPTION_DUPLICATE = 'item-label-description-duplicate';
	public const ITEM_NOT_FOUND = 'item-not-found';
	public const ITEM_REDIRECTED = 'redirected-item';
	public const ITEM_DATA_INVALID_FIELD = 'item-data-invalid-field';
	public const ITEM_DATA_UNEXPECTED_FIELD = 'unexpected-field';
	public const LABEL_DESCRIPTION_SAME_VALUE = 'label-description-same-value';
	public const LABEL_EMPTY = 'label-empty';
	public const LABEL_NOT_DEFINED = 'label-not-defined';
	public const LABEL_TOO_LONG = 'label-too-long';
	public const MISSING_JSON_PATCH_FIELD = 'missing-json-patch-field';
	public const MISSING_LABELS_AND_DESCRIPTIONS = 'missing-labels-and-descriptions';
	public const PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE = 'patched-item-label-description-duplicate';
	public const PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE = 'patched-item-label-description-same-value';
	public const PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE = 'patched-property-label-description-same-value';
	public const PATCHED_PROPERTY_LABEL_DUPLICATE = 'patched-property-label-duplicate';
	public const PATCHED_LABEL_EMPTY = 'patched-label-empty';
	public const PATCHED_LABEL_INVALID = 'patched-label-invalid';
	public const PATCHED_LABEL_INVALID_LANGUAGE_CODE = 'patched-labels-invalid-language-code';
	public const PATCHED_LABEL_TOO_LONG = 'patched-label-too-long';
	public const PATCHED_DESCRIPTION_EMPTY = 'patched-description-empty';
	public const PATCHED_DESCRIPTION_INVALID = 'patched-description-invalid';
	public const PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE = 'patched-descriptions-invalid-language-code';
	public const PATCHED_DESCRIPTION_TOO_LONG = 'patched-description-too-long';
	public const PATCHED_ALIAS_EMPTY = 'patched-alias-empty';
	public const PATCHED_ALIASES_INVALID_FIELD = 'patched-aliases-invalid-field';
	public const PATCHED_ALIASES_INVALID_LANGUAGE_CODE = 'patched-aliases-invalid-language-code';
	public const PATCHED_ALIAS_TOO_LONG = 'patched-alias-too-long';
	public const PATCHED_ALIAS_DUPLICATE = 'patched-duplicate-alias';
	public const PATCHED_STATEMENT_INVALID_FIELD = 'patched-statement-invalid-field';
	public const PATCHED_STATEMENT_MISSING_FIELD = 'patched-statement-missing-field';
	public const PATCHED_SITELINK_INVALID_SITE_ID = 'patched-sitelink-invalid-site-id';
	public const PATCHED_SITELINK_MISSING_TITLE = 'patched-sitelink-missing-title';
	public const PATCHED_SITELINK_TITLE_EMPTY = 'patched-sitelink-title-empty';
	public const PATCHED_SITELINK_INVALID_TITLE = 'patched-sitelink-invalid-title';
	public const PATCHED_SITELINK_TITLE_DOES_NOT_EXIST = 'patched-sitelink-title-does-not-exist';
	public const PATCHED_SITELINK_INVALID_BADGE = 'patched-sitelink-invalid-badge';
	public const PATCHED_SITELINK_ITEM_NOT_A_BADGE = 'patched-sitelink-item-not-a-badge';
	public const PATCHED_SITELINK_CONFLICT = 'patched-sitelink-conflict';
	public const PATCHED_SITELINK_URL_NOT_MODIFIABLE = 'url-not-modifiable';
	public const PATCHED_SITELINK_BADGES_FORMAT = 'patched-sitelink-badges-format';
	public const PATCH_TARGET_NOT_FOUND = 'patch-target-not-found';
	public const PATCH_TEST_FAILED = 'patch-test-failed';
	public const PERMISSION_DENIED = 'permission-denied';
	public const PROPERTY_NOT_FOUND = 'property-not-found';
	public const PROPERTY_LABEL_DUPLICATE = 'property-label-duplicate';
	public const SITELINK_CONFLICT = 'sitelink-conflict';
	public const SITELINK_NOT_DEFINED = 'sitelink-not-defined';
	public const SITELINK_DATA_MISSING_TITLE = 'sitelink-data-missing-title';
	public const TITLE_FIELD_EMPTY = 'title-field-empty';
	public const INVALID_TITLE_FIELD = 'invalid-title-field';
	public const INVALID_INPUT_SITELINK_BADGE = 'invalid-input-sitelink-badge';
	public const INVALID_SITELINK_BADGES_FORMAT = 'invalid-sitelink-badges-format';
	public const ITEM_NOT_A_BADGE = 'item-not-a-badge';

	public const SITELINK_TITLE_NOT_FOUND = 'title-does-not-exist';
	public const STATEMENT_DATA_INVALID_FIELD = 'statement-data-invalid-field';
	public const STATEMENT_DATA_MISSING_FIELD = 'statement-data-missing-field';
	public const STATEMENT_NOT_FOUND = 'statement-not-found';
	public const UNEXPECTED_ERROR = 'unexpected-error';

	public const CONTEXT_ACTUAL_VALUE = 'actual-value';
	public const CONTEXT_ALIAS = 'alias';
	public const CONTEXT_CHARACTER_LIMIT = 'character-limit';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_FIELD = 'field';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_LANGUAGE = 'language';

	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';
	public const CONTEXT_MATCHING_PROPERTY_ID = 'matching-property-id';
	public const CONTEXT_OPERATION = 'operation';
	public const CONTEXT_PATH = 'path';
	public const CONTEXT_PROPERTY_ID = 'property-id';
	public const CONTEXT_SITE_ID = 'site-id';
	public const CONTEXT_TITLE = 'title';
	public const CONTEXT_BADGE = 'badge';
	public const CONTEXT_BADGES = 'badges';
	public const CONTEXT_SUBJECT_ID = 'subject-id';
	public const CONTEXT_VALUE = 'value';

	public const EXPECTED_CONTEXT_KEYS = [
		self::ALIAS_DUPLICATE => [ self::CONTEXT_ALIAS ],
		self::ALIAS_EMPTY => [],
		self::ALIAS_LIST_EMPTY => [],
		self::ALIAS_TOO_LONG => [ self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::ALIASES_NOT_DEFINED => [],
		self::COMMENT_TOO_LONG => [],
		self::DESCRIPTION_EMPTY => [],
		self::DESCRIPTION_NOT_DEFINED => [],
		self::DESCRIPTION_TOO_LONG => [ self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::INVALID_ALIAS => [ self::CONTEXT_ALIAS ],
		self::INVALID_DESCRIPTION => [],
		self::INVALID_EDIT_TAG => [],
		self::INVALID_FIELD => [],
		self::INVALID_ITEM_ID => [],
		self::INVALID_LABEL => [],
		self::INVALID_LANGUAGE_CODE => [],
		self::INVALID_OPERATION_CHANGED_PROPERTY => [],
		self::INVALID_OPERATION_CHANGED_STATEMENT_ID => [],
		self::INVALID_PATCH => [],
		self::INVALID_PATCH_FIELD_TYPE => [ self::CONTEXT_OPERATION, self::CONTEXT_FIELD ],
		self::INVALID_PATCH_OPERATION => [ self::CONTEXT_OPERATION ],
		self::INVALID_PROPERTY_ID => [ self::CONTEXT_PROPERTY_ID ],
		self::INVALID_SITE_ID => [],
		self::INVALID_STATEMENT_ID => [],
		self::INVALID_STATEMENT_SUBJECT_ID => [ self::CONTEXT_SUBJECT_ID ],
		self::ITEM_LABEL_DESCRIPTION_DUPLICATE => [
			self::CONTEXT_LANGUAGE,
			self::CONTEXT_LABEL,
			self::CONTEXT_DESCRIPTION,
			self::CONTEXT_MATCHING_ITEM_ID,
		],
		self::ITEM_NOT_FOUND => [],
		self::ITEM_REDIRECTED => [],
		self::ITEM_DATA_INVALID_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::ITEM_DATA_UNEXPECTED_FIELD => [ self::CONTEXT_FIELD ],
		self::LABEL_DESCRIPTION_SAME_VALUE => [ self::CONTEXT_LANGUAGE ],
		self::LABEL_EMPTY => [],
		self::LABEL_NOT_DEFINED => [],
		self::LABEL_TOO_LONG => [ self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::MISSING_JSON_PATCH_FIELD => [ self::CONTEXT_OPERATION, self::CONTEXT_FIELD ],
		self::MISSING_LABELS_AND_DESCRIPTIONS => [],
		self::PATCHED_ITEM_LABEL_DESCRIPTION_DUPLICATE => [
			self::CONTEXT_LANGUAGE,
			self::CONTEXT_LABEL,
			self::CONTEXT_DESCRIPTION,
			self::CONTEXT_MATCHING_ITEM_ID,
		],
		self::PATCHED_ITEM_LABEL_DESCRIPTION_SAME_VALUE => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_PROPERTY_LABEL_DESCRIPTION_SAME_VALUE => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_LABEL_EMPTY => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_LABEL_INVALID => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE ],
		self::PATCHED_LABEL_INVALID_LANGUAGE_CODE => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_LABEL_TOO_LONG => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::PATCHED_DESCRIPTION_EMPTY => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_DESCRIPTION_INVALID => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE ],
		self::PATCHED_DESCRIPTION_INVALID_LANGUAGE_CODE => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_DESCRIPTION_TOO_LONG => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::PATCHED_ALIAS_EMPTY => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_ALIASES_INVALID_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::PATCHED_ALIASES_INVALID_LANGUAGE_CODE => [ self::CONTEXT_LANGUAGE ],
		self::PATCHED_ALIAS_TOO_LONG => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE, self::CONTEXT_CHARACTER_LIMIT ],
		self::PATCHED_ALIAS_DUPLICATE => [ self::CONTEXT_LANGUAGE, self::CONTEXT_VALUE ],
		self::PATCHED_STATEMENT_INVALID_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::PATCHED_STATEMENT_MISSING_FIELD => [ self::CONTEXT_PATH ],
		self::PATCHED_SITELINK_INVALID_SITE_ID => [ self::CONTEXT_SITE_ID ],
		self::PATCHED_SITELINK_MISSING_TITLE => [ self::CONTEXT_SITE_ID ],
		self::PATCHED_SITELINK_TITLE_EMPTY => [ self::CONTEXT_SITE_ID ],
		self::PATCHED_SITELINK_INVALID_TITLE => [ self::CONTEXT_SITE_ID, self::CONTEXT_TITLE ],
		self::PATCHED_SITELINK_TITLE_DOES_NOT_EXIST => [ self::CONTEXT_SITE_ID, self::CONTEXT_TITLE ],
		self::PATCHED_SITELINK_INVALID_BADGE => [ self::CONTEXT_SITE_ID, self::CONTEXT_BADGE ],
		self::PATCHED_SITELINK_ITEM_NOT_A_BADGE => [ self::CONTEXT_SITE_ID, self::CONTEXT_BADGE ],
		self::PATCHED_SITELINK_BADGES_FORMAT => [ self::CONTEXT_SITE_ID, self::CONTEXT_BADGES ],
		self::PATCHED_SITELINK_CONFLICT => [ self::CONTEXT_MATCHING_ITEM_ID, self::CONTEXT_SITE_ID ],
		self::PATCHED_SITELINK_URL_NOT_MODIFIABLE => [ self::CONTEXT_SITE_ID ],
		self::PATCH_TARGET_NOT_FOUND => [ self::CONTEXT_OPERATION, self::CONTEXT_FIELD ],
		self::PATCH_TEST_FAILED => [ self::CONTEXT_OPERATION, self::CONTEXT_ACTUAL_VALUE ],
		self::PERMISSION_DENIED => [],
		self::PROPERTY_NOT_FOUND => [],
		self::PROPERTY_LABEL_DUPLICATE => [
			self::CONTEXT_LANGUAGE,
			self::CONTEXT_LABEL,
			self::CONTEXT_MATCHING_PROPERTY_ID,
		],
		self::PATCHED_PROPERTY_LABEL_DUPLICATE => [
			self::CONTEXT_LANGUAGE,
			self::CONTEXT_LABEL,
			self::CONTEXT_MATCHING_PROPERTY_ID,
		],
		self::STATEMENT_DATA_INVALID_FIELD => [ self::CONTEXT_PATH, self::CONTEXT_VALUE ],
		self::STATEMENT_DATA_MISSING_FIELD => [ self::CONTEXT_PATH ],
		self::STATEMENT_NOT_FOUND => [],
		self::SITELINK_CONFLICT => [ self::CONTEXT_MATCHING_ITEM_ID ],
		self::SITELINK_NOT_DEFINED => [],
		self::SITELINK_DATA_MISSING_TITLE => [],
		self::TITLE_FIELD_EMPTY => [],
		self::INVALID_TITLE_FIELD => [],
		self::INVALID_INPUT_SITELINK_BADGE => [ self::CONTEXT_BADGE ],
		self::INVALID_SITELINK_BADGES_FORMAT => [],
		self::ITEM_NOT_A_BADGE => [ self::CONTEXT_BADGE ],
		self::SITELINK_TITLE_NOT_FOUND => [],
		self::UNEXPECTED_ERROR => [],
	];

	/**
	 * Depending on the use case and whether it's operating on a single resource or a list, errors may include path information in the
	 * context.
	 */
	private const ADDITIONAL_PATH_CONTEXT = [
		self::LABEL_EMPTY => [ self::CONTEXT_LANGUAGE ],
		self::INVALID_LANGUAGE_CODE => [ self::CONTEXT_LANGUAGE, self::CONTEXT_PATH ],
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
			array_merge( self::EXPECTED_CONTEXT_KEYS[$code], self::ADDITIONAL_PATH_CONTEXT[$code] ?? [] )
		) );
		if ( $unexpectedContext ) {
			throw new LogicException( "Error context for '$code' should not contain keys: " . json_encode( $unexpectedContext ) );
		}
		$missingContext = array_values( array_diff( self::EXPECTED_CONTEXT_KEYS[$code], $contextKeys ) );
		if ( $missingContext ) {
			throw new LogicException( "Error context for '$code' should contain keys: " . json_encode( $missingContext ) );
		}
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
