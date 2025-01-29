<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Crud\Application\Validation\LanguageCodeValidator;

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
				UseCaseError::INVALID_PATH_PARAMETER,
				"Invalid path parameter: 'language_code'",
				[ UseCaseError::CONTEXT_PARAMETER => 'language_code' ]
			);
		}
		return $request->getLanguageCode();
	}

}
