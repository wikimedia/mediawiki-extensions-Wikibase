<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use LogicException;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidDescriptionException;
use Wikibase\Repo\RestApi\Application\Serialization\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class ItemLabelsAndDescriptionsValidator {

	public const CODE_INVALID_FIELD = 'invalid-field';
	public const CODE_EMPTY_LABEL = 'label-empty';
	public const CODE_EMPTY_DESCRIPTION = 'description-empty';
	public const CODE_INVALID_LABEL = 'invalid-label';
	public const CODE_INVALID_DESCRIPTION = 'invalid-description';

	public const CONTEXT_FIELD_NAME = 'field';
	public const CONTEXT_FIELD_VALUE = 'value';
	public const CONTEXT_FIELD_LABEL = 'label';
	public const CONTEXT_FIELD_DESCRIPTION = 'description';
	public const CONTEXT_FIELD_LANGUAGE = 'language';

	private ItemLabelValidator $itemLabelValidator;
	private ItemDescriptionValidator $itemDescriptionValidator;
	private LanguageCodeValidator $labelLanguageCodeValidator;
	private LanguageCodeValidator $descriptionLanguageCodeValidator;
	private LabelsDeserializer $labelsDeserializer;
	private DescriptionsDeserializer $descriptionsDeserializer;

	private ?TermList $deserializedLabels = null;
	private ?TermList $deserializedDescriptions = null;

	public function __construct(
		ItemLabelValidator $itemLabelValidator,
		ItemDescriptionValidator $itemDescriptionValidator,
		LanguageCodeValidator $labelLanguageCodeValidator,
		LanguageCodeValidator $descriptionLanguageCodeValidator,
		LabelsDeserializer $labelsDeserializer,
		DescriptionsDeserializer $descriptionsDeserializer
	) {
		$this->itemLabelValidator = $itemLabelValidator;
		$this->labelsDeserializer = $labelsDeserializer;
		$this->labelLanguageCodeValidator = $labelLanguageCodeValidator;
		$this->descriptionLanguageCodeValidator = $descriptionLanguageCodeValidator;
		$this->descriptionsDeserializer = $descriptionsDeserializer;
		$this->itemDescriptionValidator = $itemDescriptionValidator;
	}

	public function validate( array $labels, array $descriptions ): ?ValidationError {
		$labelsError = $this->deserializeLabels( $labels );
		if ( $labelsError ) {
			return $labelsError;
		}
		$descriptionsError = $this->deserializeDescriptions( $descriptions );
		if ( $descriptionsError ) {
			return $descriptionsError;
		}

		return $this->validateLabels( $this->deserializedLabels, $this->deserializedDescriptions ) ??
			   $this->validateDescriptions( $this->deserializedDescriptions, $this->deserializedLabels );
	}

	private function deserializeLabels( array $labels ): ?ValidationError {
		if ( count( $labels ) > 0 && array_is_list( $labels ) ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[
					self::CONTEXT_FIELD_NAME => 'labels',
					self::CONTEXT_FIELD_VALUE => $labels,
				]
			);
		}

		foreach ( $labels as $languageCode => $labelText ) {
			$languageValidationError = $this->labelLanguageCodeValidator->validate( (string)$languageCode );
			if ( $languageValidationError ) {
				return new ValidationError(
					LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
					array_merge(
						$languageValidationError->getContext(),
						[ LanguageCodeValidator::CONTEXT_PATH_VALUE => 'labels' ]
					)
				);
			}
		}

		try {
			$this->deserializedLabels = $this->labelsDeserializer->deserialize( $labels );
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
		}

		return null;
	}

	private function validateLabels( TermList $labels, TermList $descriptions ): ?ValidationError {
		foreach ( $labels as $label ) {
			$labelValidationError = $this->itemLabelValidator
				->validate( $label->getLanguageCode(), $label->getText(), $descriptions );
			if ( $labelValidationError ) {
				return $labelValidationError;
			}
		}

		return null;
	}

	private function deserializeDescriptions( array $descriptions ): ?ValidationError {
		if ( count( $descriptions ) > 0 && array_is_list( $descriptions ) ) {
			return new ValidationError(
				self::CODE_INVALID_FIELD,
				[
					self::CONTEXT_FIELD_NAME => 'descriptions',
					self::CONTEXT_FIELD_VALUE => $descriptions,
				]
			);
		}

		foreach ( $descriptions as $languageCode => $descriptionText ) {
			$languageValidationError = $this->descriptionLanguageCodeValidator->validate( (string)$languageCode );
			if ( $languageValidationError !== null ) {
				return new ValidationError(
					LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
					array_merge(
						$languageValidationError->getContext(),
						[ LanguageCodeValidator::CONTEXT_PATH_VALUE => 'descriptions' ]
					)
				);
			}
		}

		try {
			$this->deserializedDescriptions = $this->descriptionsDeserializer->deserialize( $descriptions );
		} catch ( EmptyDescriptionException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_DESCRIPTION,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidDescriptionException $e ) {
			return new ValidationError(
				self::CODE_INVALID_DESCRIPTION,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField(), self::CONTEXT_FIELD_DESCRIPTION => $e->getValue() ]
			);
		}

		return null;
	}

	private function validateDescriptions( TermList $descriptions, TermList $labels ): ?ValidationError {
		foreach ( $descriptions as $description ) {
			$descriptionValidationError = $this->itemDescriptionValidator->validate(
				$description->getLanguageCode(),
				$description->getText(),
				$labels
			);
			if ( $descriptionValidationError ) {
				return $descriptionValidationError;
			}
		}

		return null;
	}

	public function getValidatedLabels(): TermList {
		if ( $this->deserializedLabels === null ) {
			throw new LogicException( 'getValidatedLabels() called before validate()' );
		}

		return $this->deserializedLabels;
	}

	public function getValidatedDescriptions(): TermList {
		if ( $this->deserializedDescriptions === null ) {
			throw new LogicException( 'getValidatedDescriptions() called before validate()' );
		}

		return $this->deserializedDescriptions;
	}

}
