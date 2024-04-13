<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemDescriptionValidator {

	public const CODE_INVALID = 'invalid-description';
	public const CODE_EMPTY = 'description-empty';
	public const CODE_TOO_LONG = 'description-too-long';
	public const CODE_DESCRIPTION_SAME_AS_LABEL = 'description-same-as-label';
	public const CODE_DESCRIPTION_LABEL_DUPLICATE = 'description-label-duplicate';

	public const CONTEXT_LIMIT = 'character-limit';
	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';

	public function validate( string $language, string $descriptionText, TermList $existingLabels ): ?ValidationError;

}
