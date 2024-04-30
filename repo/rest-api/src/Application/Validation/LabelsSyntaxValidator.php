<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class LabelsSyntaxValidator {
	public const CODE_LABELS_NOT_ASSOCIATIVE = 'labels-not-associative';
	public const CODE_EMPTY_LABEL = 'label-empty';
	public const CODE_INVALID_LABEL_TYPE = 'invalid-label-type';

	public const CONTEXT_FIELD_NAME = 'field';
	public const CONTEXT_FIELD_VALUE = 'value';
	public const CONTEXT_FIELD_LABEL = 'label';
	public const CONTEXT_FIELD_LANGUAGE = 'language';

	private LabelsDeserializer $deserializer;
	private LanguageCodeValidator $languageCodeValidator;
	private PartiallyValidatedLabels $deserializedLabels;

	public function __construct( LabelsDeserializer $deserializer, LanguageCodeValidator $languageCodeValidator ) {
		$this->deserializer = $deserializer;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	public function validate( array $serialization ): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_LABELS_NOT_ASSOCIATIVE );
		}

		return $this->validateLanguageCodes( array_keys( $serialization ) )
			?: $this->validateSerialization( $serialization );
	}

	private function validateLanguageCodes( array $languageCodes ): ?ValidationError {
		foreach ( $languageCodes as $languageCode ) {
			$languageValidationError = $this->languageCodeValidator->validate( (string)$languageCode );
			if ( $languageValidationError ) {
				return new ValidationError(
					LanguageCodeValidator::CODE_INVALID_LANGUAGE_CODE,
					array_merge(
						$languageValidationError->getContext(),
						[ LanguageCodeValidator::CONTEXT_PATH => 'labels' ]
					)
				);
			}
		}

		return null;
	}

	private function validateSerialization( array $serialization ): ?ValidationError {
		try {
			$this->deserializedLabels = new PartiallyValidatedLabels( $this->deserializer->deserialize( $serialization ) );
		} catch ( EmptyLabelException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_LABEL,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidLabelException $e ) {
			return new ValidationError(
				self::CODE_INVALID_LABEL_TYPE,
				[ self::CONTEXT_FIELD_LANGUAGE => $e->getField(), self::CONTEXT_FIELD_LABEL => $e->getValue() ]
			);
		}

		return null;
	}

	public function getPartiallyValidatedLabels(): PartiallyValidatedLabels {
		return $this->deserializedLabels;
	}
}
