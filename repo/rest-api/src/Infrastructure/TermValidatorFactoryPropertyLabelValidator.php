<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\PropertyLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryPropertyLabelValidator implements PropertyLabelValidator {

	private TermValidatorFactory $termValidatorFactory;
	private TermsCollisionDetector $termsCollisionDetector;

	public function __construct( TermValidatorFactory $termValidatorFactory, TermsCollisionDetector $termsCollisionDetector ) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
	}

	public function validate( string $language, string $labelText, TermList $existingDescriptions ): ?ValidationError {
		return $this->validateLabelText( $labelText, $language )
			   ?? $this->validateLabelWithDescriptions( $language, $labelText, $existingDescriptions );
	}

	public function validateLabelText( string $labelText, string $language ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getLabelValidator( Property::ENTITY_TYPE )
			->validate( $labelText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'label-too-short':
					return new ValidationError( self::CODE_EMPTY, [ self::CONTEXT_LANGUAGE => $language ] );
				case 'label-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_LABEL => $labelText,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
							self::CONTEXT_LANGUAGE => $language,
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[
							self::CONTEXT_LABEL => $labelText,
							self::CONTEXT_LANGUAGE => $language,
						]
					);
			}
		}

		return null;
	}

	private function validateLabelWithDescriptions( string $language, string $label, TermList $existingDescriptions ): ?ValidationError {
		$entityId = $this->termsCollisionDetector->detectLabelCollision( $language, $label );
		if ( $entityId instanceof PropertyId ) {
			return new ValidationError(
				self::CODE_LABEL_DUPLICATE,
				[
					self::CONTEXT_LANGUAGE => $language,
					self::CONTEXT_LABEL => $label,
					self::CONTEXT_CONFLICTING_PROPERTY_ID => (string)$entityId,
				]
			);
		}

		// skip if Property does not have a description
		if ( !$existingDescriptions->hasTermForLanguage( $language ) ) {
			return null;
		}

		$description = $existingDescriptions->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_EQUAL,
				[ self::CONTEXT_LANGUAGE => $language ],
			);
		}

		return null;
	}
}
