<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\RestApi\Application\Serialization\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\ItemLabelTextValidator;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class PatchedLabelsValidator {

	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_VALUE = 'value';

	private LabelsDeserializer $labelsDeserializer;
	private ItemLabelTextValidator $labelTextValidator;
	private LanguageCodeValidator $languageCodeValidator;

	public function __construct(
		LabelsDeserializer $labelsDeserializer,
		ItemLabelTextValidator $labelTextValidator,
		LanguageCodeValidator $languageCodeValidator
	) {
		$this->labelsDeserializer = $labelsDeserializer;
		$this->labelTextValidator = $labelTextValidator;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( array $labelsSerialization ): TermList {
		try {
			$labels = $this->labelsDeserializer->deserialize( $labelsSerialization );
		} catch ( EmptyLabelException $e ) {
			$languageCode = $e->getField();
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_EMPTY,
				"Changed label for '$languageCode' cannot be empty",
				[ self::CONTEXT_LANGUAGE => $languageCode ]
			);
		}

		foreach ( $labels as $label ) {
			$this->validateLabelText( $label );
			$this->validateLanguageCode( $label );
		}

		return $labels;
	}

	private function validateLabelText( Term $label ): void {
		$validationError = $this->labelTextValidator->validate( $label->getText() );
		if ( !$validationError ) {
			return;
		}

		switch ( $validationError->getCode() ) {
			case ItemLabelTextValidator::CODE_INVALID:
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_INVALID,
					"Changed label for '{$label->getLanguageCode()}' is invalid: {$label->getText()}",
					[
						self::CONTEXT_LANGUAGE => $label->getLanguageCode(),
						self::CONTEXT_VALUE => $label->getText(),
					]
				);
			case ItemLabelTextValidator::CODE_TOO_LONG:
				$maxLabelLength = $validationError->getContext()[ItemLabelTextValidator::CONTEXT_LIMIT];
				throw new UseCaseError(
					UseCaseError::PATCHED_LABEL_TOO_LONG,
					"Changed label for '{$label->getLanguageCode()}' must not be more than $maxLabelLength characters long",
					array_merge( $validationError->getContext(), [ self::CONTEXT_LANGUAGE => $label->getLanguageCode() ] )
				);
		}
	}

	private function validateLanguageCode( Term $label ): void {
		$validationError = $this->languageCodeValidator->validate( $label->getLanguageCode() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::PATCHED_LABEL_INVALID_LANGUAGE_CODE,
				"Not a valid language code '{$label->getLanguageCode()}' in changed labels",
				[ self::CONTEXT_LANGUAGE => $label->getLanguageCode() ]
			);
		}
	}

}
