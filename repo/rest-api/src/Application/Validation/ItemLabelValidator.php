<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemLabelValidator {

	public const CODE_LABEL_SAME_AS_DESCRIPTION = 'item-label-validator-code-label-same-as-description';
	public const CODE_LABEL_DESCRIPTION_DUPLICATE = 'item-label-validator-code-label-description-duplicate';
	public const CODE_INVALID = 'item-label-validator-code-invalid-label';
	public const CODE_EMPTY = 'item-label-validator-code-label-empty';
	public const CODE_TOO_LONG = 'item-label-validator-code-label-too-long';

	public const CONTEXT_LANGUAGE = 'item-label-validator-context-language';
	public const CONTEXT_LABEL = 'item-label-validator-context-label';
	public const CONTEXT_DESCRIPTION = 'item-label-validator-context-description';
	public const CONTEXT_MATCHING_ITEM_ID = 'item-label-validator-context-matching-item-id';
	public const CONTEXT_LIMIT = 'item-label-validator-context-character-limit';

	public function validate( string $language, string $labelText, TermList $existingDescriptions ): ?ValidationError;

}
