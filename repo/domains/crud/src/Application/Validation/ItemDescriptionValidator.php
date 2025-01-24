<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemDescriptionValidator {

	public const CODE_INVALID = 'item-description-validator-code-invalid-description';
	public const CODE_EMPTY = 'item-description-validator-code-description-empty';
	public const CODE_TOO_LONG = 'item-description-validator-code-description-too-long';
	public const CODE_DESCRIPTION_SAME_AS_LABEL = 'item-description-validator-code-description-same-as-label';
	public const CODE_DESCRIPTION_LABEL_DUPLICATE = 'item-description-validator-code-description-label-duplicate';

	public const CONTEXT_LIMIT = 'item-description-validator-context-character-limit';
	public const CONTEXT_LANGUAGE = 'item-description-validator-context-language';
	public const CONTEXT_LABEL = 'item-description-validator-context-label';
	public const CONTEXT_DESCRIPTION = 'item-description-validator-context-description';
	public const CONTEXT_CONFLICTING_ITEM_ID = 'item-description-validator-context-conflicting-item-id';

	public function validate( string $language, string $descriptionText, TermList $existingLabels ): ?ValidationError;

}
