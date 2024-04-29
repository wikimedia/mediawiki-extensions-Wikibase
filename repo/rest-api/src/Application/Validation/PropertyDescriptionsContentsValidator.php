<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class PropertyDescriptionsContentsValidator {

	private ?TermList $validatedDescriptions = null;
	private PropertyDescriptionValidator $descriptionValidator;

	public function __construct( PropertyDescriptionValidator $descriptionValidator ) {
		$this->descriptionValidator = $descriptionValidator;
	}

	public function validate(
		PartiallyValidatedDescriptions $descriptions,
		PropertyId $propertyId,
		array $languages = null
	): ?ValidationError {
		$languages ??= array_keys( $descriptions->toTextArray() );
		foreach ( $languages as $language ) {
			$error = $this->descriptionValidator->validate( $propertyId, $language, $descriptions->getByLanguage( $language )->getText() );
			if ( $error ) {
				return $error;
			}
		}
		$this->validatedDescriptions = $descriptions->asPlainTermList();

		return null;
	}

	public function getValidatedDescriptions(): TermList {
		return $this->validatedDescriptions;
	}
}
