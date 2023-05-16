<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelValidator {

	public const CODE_LABEL_DESCRIPTION_EQUAL = 'label-description-same-value';
	public const CODE_LABEL_DESCRIPTION_DUPLICATE = 'item-label-description-duplicate';
	public const CODE_INVALID = 'invalid-label';
	public const CODE_EMPTY = 'label-empty';
	public const CODE_TOO_LONG = 'label-too-long';

	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';
	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';

	public function validate( ItemId $itemId, string $language, string $label ): ?ValidationError;

}
