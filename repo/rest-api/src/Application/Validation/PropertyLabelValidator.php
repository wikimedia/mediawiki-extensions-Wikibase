<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyLabelValidator {

	public const CODE_LABEL_DESCRIPTION_EQUAL = 'label-description-same-value';
	public const CODE_LABEL_DUPLICATE = 'property-label-duplicate';
	public const CODE_INVALID = 'invalid-label';
	public const CODE_EMPTY = 'label-empty';
	public const CODE_TOO_LONG = 'label-too-long';

	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_LABEL = 'label';
	public const CONTEXT_MATCHING_PROPERTY_ID = 'matching-property-id';
	public const CONTEXT_LIMIT = 'character-limit';

	public function validate( PropertyId $propertyId, string $language, string $label ): ?ValidationError;

}
