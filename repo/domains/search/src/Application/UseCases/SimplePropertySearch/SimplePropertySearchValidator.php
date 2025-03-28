<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimplePropertySearch;

use LogicException;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class SimplePropertySearchValidator {
	public const LANGUAGE_QUERY_PARAM = 'language';

	private SearchLanguageValidator $languageValidator;

	public function __construct( SearchLanguageValidator $languageValidator ) {
		$this->languageValidator = $languageValidator;
	}

	/**
	 * @param SimplePropertySearchRequest $request
	 *
	 * @throws UseCaseError
	 */
	public function validate( SimplePropertySearchRequest $request ): void {
		$validationError = $this->languageValidator->validate( $request->getLanguage() );

		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case SearchLanguageValidator::CODE_INVALID_LANGUAGE_CODE:
					throw new UseCaseError(
						UseCaseError::INVALID_QUERY_PARAMETER,
						"Invalid query parameter: '" . self::LANGUAGE_QUERY_PARAM . "'",
						[ UseCaseError::CONTEXT_PARAMETER => self::LANGUAGE_QUERY_PARAM ]
					);
				default:
					throw new LogicException( 'unknown validation error code ' . $validationError->getCode() );
			}
		}
	}
}
