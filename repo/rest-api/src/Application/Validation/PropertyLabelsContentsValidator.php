<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelsContentsValidator {

	private ?TermList $validatedLabels = null;
	private PropertyLabelValidator $labelValidator;

	public function __construct( PropertyLabelValidator $labelValidator ) {
		$this->labelValidator = $labelValidator;
	}

	public function validate( PartiallyValidatedLabels $labels, PropertyId $propertyId, array $languages = null ): ?ValidationError {
		$languages ??= array_keys( $labels->toTextArray() );
		foreach ( $languages as $language ) {
			$error = $this->labelValidator->validate( $propertyId, $language, $labels->getByLanguage( $language )->getText() );
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
