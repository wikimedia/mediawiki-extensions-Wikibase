<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryItemLabelValidator implements ItemLabelValidator {

	private TermValidatorFactory $termValidatorFactory;
	private TermsCollisionDetector $termsCollisionDetector;

	public function __construct(
		TermValidatorFactory $termValidatorFactory,
		TermsCollisionDetector $termsCollisionDetector
	) {
		$this->termValidatorFactory = $termValidatorFactory;
		$this->termsCollisionDetector = $termsCollisionDetector;
	}

	public function validate( string $language, string $labelText, TermList $existingDescriptions ): ?ValidationError {
		return $this->validateLabelText( $labelText, $language )
			   ?? $this->validateLabelWithDescriptions( $language, $labelText, $existingDescriptions );
	}

	public function validateLabelText( string $labelText, string $language ): ?ValidationError {
		$result = $this->termValidatorFactory
			->getLabelValidator( Item::ENTITY_TYPE )
			->validate( $labelText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[0];
			switch ( $error->getCode() ) {
				case 'label-too-short':
					return new ValidationError(
						self::CODE_EMPTY,
						[ self::CONTEXT_LANGUAGE => $language ]
					);
				case 'label-too-long':
					return new ValidationError(
						self::CODE_TOO_LONG,
						[
							self::CONTEXT_LABEL => $labelText,
							self::CONTEXT_LANGUAGE => $language,
							self::CONTEXT_LIMIT => $error->getParameters()[0],
						]
					);
				default:
					return new ValidationError(
						self::CODE_INVALID,
						[ self::CONTEXT_LABEL => $labelText, self::CONTEXT_LANGUAGE => $language ]
					);
			}
		}

		return null;
	}

	private function validateLabelWithDescriptions(
		string $language,
		string $label,
		TermList $existingDescriptions
	): ?ValidationError {
		// skip if Item does not have a description in the language
		if ( !$existingDescriptions->hasTermForLanguage( $language ) ) {
			return null;
		}

		$description = $existingDescriptions->getByLanguage( $language )->getText();
		if ( $label === $description ) {
			return new ValidationError(
				self::CODE_LABEL_SAME_AS_DESCRIPTION,
				[ self::CONTEXT_LANGUAGE => $language ],
			);
		}

		$entityId = $this->termsCollisionDetector
			->detectLabelAndDescriptionCollision( $language, $label, $description );
		if ( $entityId instanceof ItemId ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_DUPLICATE,
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
