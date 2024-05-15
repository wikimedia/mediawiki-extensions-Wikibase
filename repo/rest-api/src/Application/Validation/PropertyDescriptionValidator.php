<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyDescriptionValidator {

	public const CODE_INVALID = 'property-description-validator-code-invalid-description';
	public const CODE_EMPTY = 'property-description-validator-code-description-empty';
	public const CODE_TOO_LONG = 'property-description-validator-code-description-too-long';
	public const CODE_LABEL_DESCRIPTION_EQUAL = 'property-description-validator-code-label-description-equal';

	public const CONTEXT_LIMIT = 'property-description-validator-context-character-limit';
	public const CONTEXT_LANGUAGE = 'property-description-validator-context-language';
	public const CONTEXT_DESCRIPTION = 'property-description-validator-context-description';

	public function validate( string $language, string $descriptionText, TermList $existingLabels ): ?ValidationError;

}
