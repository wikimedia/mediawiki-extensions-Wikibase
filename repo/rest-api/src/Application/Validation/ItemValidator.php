<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\Item;

/**
 * @license GPL-2.0-or-later
 */
interface ItemValidator {

	public const CODE_INVALID_FIELD = 'invalid-item-field';
	public const CODE_UNEXPECTED_FIELD = 'item-data-unexpected-field';
	public const CODE_MISSING_LABELS_AND_DESCRIPTIONS = 'missing-labels-and-descriptions';
	public const CODE_EMPTY_LABEL = 'label-empty';
	public const CODE_INVALID_LABEL = 'invalid-label';
	public const CODE_INVALID_LANGUAGE_CODE = 'invalid-language-code';
	public const CODE_LABEL_DESCRIPTION_SAME_VALUE = 'label-description-same-value';
	public const CODE_LABEL_DESCRIPTION_DUPLICATE = 'item-label-description-duplicate';

	public const CONTEXT_FIELD_NAME = 'field';
	public const CONTEXT_FIELD_VALUE = 'value';
	public const CONTEXT_FIELD_LABEL = 'label';
	public const CONTEXT_FIELD_DESCRIPTION = 'description';
	public const CONTEXT_FIELD_LANGUAGE = 'language';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';

	public function validate( array $itemSerialization ): ?ValidationError;

	public function getValidatedItem(): Item;

}
