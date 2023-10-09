<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyDescriptionValidator {

	public const CODE_INVALID = 'invalid-description';
	public const CODE_EMPTY = 'description-empty';
	public const CODE_TOO_LONG = 'description-too-long';
	public const CODE_LABEL_DESCRIPTION_EQUAL = 'label-description-equal';

	public const CONTEXT_LIMIT = 'character-limit';
	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_DESCRIPTION = 'description';

	public function validate( PropertyId $propertyId, string $language, string $description ): ?ValidationError;

}
