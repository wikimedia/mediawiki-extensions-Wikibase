<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

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

	public function validate( PartiallyValidatedLabels $labels, TermList $descriptions ): ?ValidationError {
		foreach ( $labels as $label ) {
			$error = $this->labelValidator->validate( $label->getLanguageCode(), $label->getText(), $descriptions );
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
