<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\ItemDescriptionValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryItemDescriptionValidator implements ItemDescriptionValidator {

	private TermValidatorFactory $termValidatorFactory;
	private TermsCollisionDetector $termsCollisionDetector;

	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		TermsCollisionDetector $termsCollisionDetector
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
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
					return new ValidationError(
						self::CODE_EMPTY,
						[ self::CONTEXT_LANGUAGE => $language ]
					);
				case 'description-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_DESCRIPTION => $descriptionText,
							self::CONTEXT_LANGUAGE => $language,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[ self::CONTEXT_DESCRIPTION => $descriptionText, self::CONTEXT_LANGUAGE => $language ]
					);
			}
		}

		return null;
	}

	private function validateDescriptionWithLabels(
		string $language,
		string $description,
		TermList $existingLabels
	): ?ValidationError {
		// skip if Item does not have a label in the language
		if ( !$existingLabels->hasTermForLanguage( $language ) ) {
			return null;
		}

		$label = $existingLabels->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_DESCRIPTION_SAME_AS_LABEL,
				[ self::CONTEXT_LANGUAGE => $language ],
			);
		}

		$entityId = $this->termsCollisionDetector
			->detectLabelAndDescriptionCollision( $language, $label, $description );
		if ( $entityId instanceof ItemId ) {
			return new ValidationError(
				self::CODE_DESCRIPTION_LABEL_DUPLICATE,
				[
					self::CONTEXT_LANGUAGE => $language,
					self::CONTEXT_LABEL => $label,
					self::CONTEXT_DESCRIPTION => $description,
					self::CONTEXT_MATCHING_ITEM_ID => (string)$entityId,
				]
			);
		}

		return null;
	}
}
