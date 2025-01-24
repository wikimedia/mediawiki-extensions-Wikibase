<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\Validation;

use Wikibase\Repo\Domains\Crud\Application\Serialization\DescriptionsDeserializer;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\EmptyDescriptionException;
use Wikibase\Repo\Domains\Crud\Application\Serialization\Exceptions\InvalidDescriptionException;

/**
 * @license GPL-2.0-or-later
 */
class DescriptionsSyntaxValidator {
	public const CODE_DESCRIPTIONS_NOT_ASSOCIATIVE = 'descriptions-syntax-validator-code-descriptions-not-associative';
	public const CODE_EMPTY_DESCRIPTION = 'descriptions-syntax-validator-code-description-empty';
	public const CODE_INVALID_DESCRIPTION_TYPE = 'descriptions-syntax-validator-code-invalid-description-type';

	public const CONTEXT_FIELD = 'descriptions-syntax-validator-context-field';
	public const CONTEXT_VALUE = 'descriptions-syntax-validator-context-value';
	public const CONTEXT_DESCRIPTION = 'descriptions-syntax-validator-context-description';
	public const CONTEXT_LANGUAGE = 'descriptions-syntax-validator-context-language';

	private DescriptionsDeserializer $deserializer;
	private DescriptionLanguageCodeValidator $languageCodeValidator;
	private PartiallyValidatedDescriptions $deserializedDescriptions;

	public function __construct( DescriptionsDeserializer $deserializer, DescriptionLanguageCodeValidator $languageCodeValidator ) {
		$this->deserializer = $deserializer;
		$this->languageCodeValidator = $languageCodeValidator;
	}

	public function validate( array $serialization, string $basePath = '' ): ?ValidationError {
		if ( count( $serialization ) && array_is_list( $serialization ) ) {
			return new ValidationError( self::CODE_DESCRIPTIONS_NOT_ASSOCIATIVE, [ self::CONTEXT_VALUE => $serialization ] );
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
			$this->deserializedDescriptions = new PartiallyValidatedDescriptions( $this->deserializer->deserialize( $serialization ) );
		} catch ( EmptyDescriptionException $e ) {
			return new ValidationError(
				self::CODE_EMPTY_DESCRIPTION,
				[ self::CONTEXT_LANGUAGE => $e->getField() ]
			);
		} catch ( InvalidDescriptionException $e ) {
			return new ValidationError(
				self::CODE_INVALID_DESCRIPTION_TYPE,
				[ self::CONTEXT_LANGUAGE => $e->getField(), self::CONTEXT_DESCRIPTION => $e->getValue() ]
			);
		}

		return null;
	}

	public function getPartiallyValidatedDescriptions(): PartiallyValidatedDescriptions {
		return $this->deserializedDescriptions;
	}
}
