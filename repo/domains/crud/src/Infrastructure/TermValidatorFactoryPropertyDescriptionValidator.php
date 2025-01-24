<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\PropertyDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryPropertyDescriptionValidator implements PropertyDescriptionValidator {

	private TermValidatorFactory $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function validate( string $language, string $descriptionText, TermList $existingLabels ): ?ValidationError {
		return $this->validateDescriptionText( $descriptionText, $language )
			   ?? $this->validateDescriptionWithLabels( $language, $descriptionText, $existingLabels );
	}

	private function validateDescriptionText( string $descriptionText, string $language ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getDescriptionValidator()
			->validate( $descriptionText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'description-too-short':
					return new ValidationError( self::CODE_EMPTY, [ self::CONTEXT_LANGUAGE => $language ] );
				case 'description-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_DESCRIPTION => $descriptionText,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
							self::CONTEXT_LANGUAGE => $language,
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[
							self::CONTEXT_DESCRIPTION => $descriptionText,
							self::CONTEXT_LANGUAGE => $language,
						]
					);
			}
		}

		return null;
	}

	private function validateDescriptionWithLabels( string $language, string $description, TermList $existingLabels ): ?ValidationError {
		// skip if Property does not have a label in the language
		if ( !$existingLabels->hasTermForLanguage( $language ) ) {
			return null;
		}

		$label = $existingLabels->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_EQUAL,
				[ self::CONTEXT_LANGUAGE => $language ]
			);
		}

		return null;
	}

}
