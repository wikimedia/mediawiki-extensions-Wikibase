<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RequestValidation;

use Wikibase\Repo\RestApi\Application\UseCases\LanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;
use Wikibase\Repo\RestApi\Application\Validation\LanguageCodeValidator;

/**
 * @license GPL-2.0-or-later
 */
class LanguageCodeRequestValidatingDeserializer {

	private LanguageCodeValidator $languageCodeValidator;

	public function __construct( LanguageCodeValidator $languageCodeValidator ) {
		$this->languageCodeValidator = $languageCodeValidator;
	}

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( LanguageCodeRequest $request ): string {
		$validationError = $this->languageCodeValidator->validate( $request->getLanguageCode() );
		if ( $validationError ) {
			throw new UseCaseError(
				UseCaseError::INVALID_LANGUAGE_CODE,
				"Not a valid language code: {$validationError->getContext()[LanguageCodeValidator::CONTEXT_LANGUAGE_CODE_VALUE]}"
			);
		}
		return $request->getLanguageCode();
	}

}
