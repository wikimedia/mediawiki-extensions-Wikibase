<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyLabelValidator {

	public const CODE_INVALID = 'property-label-validator-code-invalid-label';
	public const CODE_EMPTY = 'property-label-validator-code-label-empty';
	public const CODE_TOO_LONG = 'property-label-validator-code-label-too-long';
	public const CODE_LABEL_DUPLICATE = 'property-label-validator-code-label-duplicate';
	public const CODE_LABEL_DESCRIPTION_EQUAL = 'property-label-validator-code-label-description-same-value';

	public const CONTEXT_LANGUAGE = 'property-label-validator-context-language';
	public const CONTEXT_LABEL = 'property-label-validator-context-label';
	public const CONTEXT_MATCHING_PROPERTY_ID = 'property-label-validator-context-matching-property-id';
	public const CONTEXT_LIMIT = 'property-label-validator-context-character-limit';

	public function validate( PropertyId $propertyId, string $language, string $label ): ?ValidationError;

}
