<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\ItemId;

/**
 * @license GPL-2.0-or-later
 */
interface ItemDescriptionValidator {

	public const CODE_INVALID = 'invalid-description';
	public const CODE_EMPTY = 'description-empty';
	public const CODE_TOO_LONG = 'description-too-long';
	public const CODE_LABEL_DESCRIPTION_EQUAL = 'label-description-equal';
	public const CODE_LABEL_DESCRIPTION_DUPLICATE = 'label-description-duplicate';

	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';
	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';

	public function validate( ItemId $itemId, string $language, string $description ): ?ValidationError;

}
