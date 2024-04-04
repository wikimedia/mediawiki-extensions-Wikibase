<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use LogicException;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidFieldException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\ItemDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\UnexpectedFieldException;
use Wikibase\Repo\RestApi\Application\Validation\ItemValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Store\TermsCollisionDetector;

/**
 * @license GPL-2.0-or-later
 */
class ItemDeserializerItemValidator implements ItemValidator {

	private ?Item $deserializedItem = null;
	private ItemDeserializer $deserializer;
	private LanguageCodeValidator $languageCodeValidator;
	private TermValidatorFactoryLabelTextValidator $labelTextValidator;
	private TermsCollisionDetector $termsCollisionDetector;

	public function __construct(
		ItemDeserializer $deserializer,
		LanguageCodeValidator $languageCodeValidator,
		TermValidatorFactoryLabelTextValidator $labelTextValidator,
		TermsCollisionDetector $termsCollisionDetector
	) {
		$this->deserializer = $deserializer;
		$this->languageCodeValidator = $languageCodeValidator;
		$this->labelTextValidator = $labelTextValidator;
		$this->termsCollisionDetector = $termsCollisionDetector;
	}

	public function validate( array $itemSerialization ): ?ValidationError {
		try {
			$this->deserializedItem = $this->deserializer->deserialize( $itemSerialization );
		} catch ( UnexpectedFieldException $e ) {
			return new ValidationError( self::CODE_UNEXPECTED_FIELD, [ self::CONTEXT_FIELD_NAME => $e->getField() ] );
		} catch ( EmptyLabelException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_LABEL,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidLabelException $e ) {
			return new ValidationError(
				self::CODE_INVALID_LABEL,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField(), self::CONTEXT_FIELD_LABEL => $e->getValue() ]
			);
		} catch ( InvalidFieldException $e ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[ self::CONTEXT_FIELD_NAME => $e->getField(), self::CONTEXT_FIELD_VALUE => $e->getValue() ]
			);
		}

		if (
			$this->deserializedItem->getLabels()->isEmpty() &&
			$this->deserializedItem->getDescriptions()->isEmpty()
		) {
			return new ValidationError( self::CODE_MISSING_LABELS_AND_DESCRIPTIONS );
		}

		return $this->validateItemLabels( $this->deserializedItem->getLabels() );
	}

	public function getValidatedItem(): Item {
		if ( $this->deserializedItem === null ) {
			throw new LogicException( 'getValidatedItem() called before validate()' );
		}
		return $this->deserializedItem;
	}

	private function validateItemLabels( TermList $labels ): ?ValidationError {
		foreach ( $labels as $label ) {
			$validationError = $this->validateLanguageCode( $label->getLanguageCode(), self::CONTEXT_FIELD_LABEL ) ??
							   $this->labelTextValidator->validate( $label->getText(), $label->getLanguageCode() ) ??
							   $this->checkTermsEqualityAndDuplication( $label );
			if ( $validationError !== null ) {
				return $validationError;
			}
		}
		return null;
	}

	private function checkTermsEqualityAndDuplication( Term $label ): ?ValidationError {
		$languageCode = $label->getLanguageCode();
		if ( !$this->deserializedItem->getDescriptions()->hasTermForLanguage( $languageCode ) ) {
			return null;
		}
		$description = $this->deserializedItem->getDescriptions()->getByLanguage( $languageCode );
		if ( $description->equals( $label ) ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_SAME_VALUE,
				[ self::CONTEXT_FIELD_LANGUAGE => $label->getLanguageCode() ]
			);
		}
		$collidingItemId = $this->termsCollisionDetector->detectLabelAndDescriptionCollision(
			$languageCode,
			$label->getText(),
			$description->getText()
		);
		if ( $collidingItemId !== null ) {
			return new ValidationError(
				self::CODE_LABEL_DESCRIPTION_DUPLICATE,
				[
					self::CONTEXT_FIELD_LANGUAGE => $label->getLanguageCode(),
					self::CONTEXT_FIELD_LABEL => $label->getText(),
					self::CONTEXT_FIELD_DESCRIPTION => $description->getText(),
					self::CONTEXT_MATCHING_ITEM_ID => $collidingItemId->getSerialization(),
				]
			);
		}
		return null;
	}

	private function validateLanguageCode( string $languageCode, string $field ): ?ValidationError {
		if ( $this->languageCodeValidator->validate( $languageCode ) ) {
			return new ValidationError(
				self::CODE_INVALID_LANGUAGE_CODE,
				[
					self::CONTEXT_FIELD_NAME => $field,
					self::CONTEXT_FIELD_LANGUAGE => $languageCode,
				]
			);
		}
		return null;
	}

}
