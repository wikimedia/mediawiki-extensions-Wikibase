<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelsContentsValidator {

	private ?TermList $validatedLabels = null;
	private ItemLabelValidator $labelValidator;

	public function __construct( ItemLabelValidator $labelValidator ) {
		$this->labelValidator = $labelValidator;
	}

	public function validate( PartiallyValidatedLabels $labels, TermList $descriptions, ?array $languages = null ): ?ValidationError {
		$languages ??= array_keys( $labels->toTextArray() );
		foreach ( $languages as $language ) {
			$error = $this->labelValidator->validate( $language, $labels->getByLanguage( $language )->getText(), $descriptions );
			if ( $error ) {
				return $error;
			}
		}
		$this->validatedLabels = $labels->asPlainTermList();

		return null;
	}

	public function getValidatedLabels(): TermList {
		return $this->validatedLabels;
	}
}
