<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class ItemDescriptionsContentsValidator {

	private ?TermList $validatedDescriptions = null;
	private ItemDescriptionValidator $descriptionValidator;

	public function __construct( ItemDescriptionValidator $descriptionValidator ) {
		$this->descriptionValidator = $descriptionValidator;
	}

	public function validate( PartiallyValidatedDescriptions $descriptions, TermList $labels, array $languages = null ): ?ValidationError {
		$languages ??= array_keys( $descriptions->toTextArray() );
		foreach ( $languages as $language ) {
			$error = $this->descriptionValidator->validate( $language, $descriptions->getByLanguage( $language )->getText(), $labels );
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
