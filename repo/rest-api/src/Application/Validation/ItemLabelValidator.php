<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelValidator {

	public const CODE_LABEL_SAME_AS_DESCRIPTION = 'label-same-as-description';
	public const CODE_LABEL_DESCRIPTION_DUPLICATE = 'label-description-duplicate';
	public const CODE_INVALID = 'invalid-label';
	public const CODE_EMPTY = 'label-empty';
	public const CODE_TOO_LONG = 'label-too-long';

	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_DESCRIPTION = 'description';
	public const CONTEXT_MATCHING_ITEM_ID = 'matching-item-id';
	public const CONTEXT_LIMIT = 'character-limit';

	public function validate( string $language, string $labelText, TermList $existingDescriptions ): ?ValidationError;

}
