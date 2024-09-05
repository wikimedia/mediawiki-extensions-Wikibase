<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\EmptyLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\Exceptions\InvalidLabelException;
use Wikibase\Repo\RestApi\Application\Serialization\LabelsDeserializer;

/**
 * @license GPL-2.0-or-later
 */
class LabelsSyntaxValidator {
	public const CODE_LABELS_NOT_ASSOCIATIVE = 'labels-syntax-validator-code-labels-not-associative';
	public const CODE_EMPTY_LABEL = 'labels-syntax-validator-code-label-empty';
	public const CODE_INVALID_LABEL_TYPE = 'labels-syntax-validator-code-invalid-label-type';

	public const CONTEXT_FIELD = 'labels-syntax-validator-context-field';
	public const CONTEXT_VALUE = 'labels-syntax-validator-context-value';
	public const CONTEXT_LABEL = 'labels-syntax-validator-context-label';
	public const CONTEXT_LANGUAGE = 'labels-syntax-validator-context-language';

	private LabelsDeserializer $deserializer;
	private LabelLanguageCodeValidator $languageCodeValidator;
	private PartiallyValidatedLabels $deserializedLabels;

	public function __construct( LabelsDeserializer $deserializer, LabelLanguageCodeValidator $languageCodeValidator ) {
		$this->deserializer = $deserializer;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	public function validate( array $serialization, string $basePath = '' ): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_LABELS_NOT_ASSOCIATIVE, [ self::CONTEXT_VALUE => $serialization ] );
		}

		return $this->validateLanguageCodes( array_keys( $serialization ), $basePath )
			?: $this->validateSerialization( $serialization );
	}

	private function validateLanguageCodes( array $languageCodes, string $basePath ): ?ValidationError {
		foreach ( $languageCodes as $languageCode ) {
			$languageValidationError = $this->languageCodeValidator->validate( (string)$languageCode, $basePath );
			if ( $languageValidationError ) {
				return $languageValidationError;
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
				[ self::CONTEXT_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidLabelException $e ) {
			return new ValidationError(
				self::CODE_INVALID_LABEL_TYPE,
				[ self::CONTEXT_LANGUAGE => $e->getField(), self::CONTEXT_LABEL => $e->getValue() ]
			);
		}

		return null;
	}

	public function getPartiallyValidatedLabels(): PartiallyValidatedLabels {
		return $this->deserializedLabels;
	}
}
