<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\SimpleItemSearch;

use LogicException;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class SimpleItemSearchValidator {

	public const LANGUAGE_QUERY_PARAM = 'language';
	public const LIMIT_QUERY_PARAM = 'limit';
	public const OFFSET_QUERY_PARAM = 'offset';

	private const MAX_LIMIT = 500;

	private SearchLanguageValidator $languageValidator;

	public function __construct( SearchLanguageValidator $languageValidator ) {
		$this->languageValidator = $languageValidator;
	}

	/**
	 * @param SimpleItemSearchRequest $request
	 *
	 * @throws UseCaseError
	 */
	public function validate( SimpleItemSearchRequest $request ): void {
		$validationError = $this->languageValidator->validate( $request->getLanguage() );

		if ( $validationError ) {
			switch ( $validationError->getCode() ) {
				case SearchLanguageValidator::CODE_INVALID_LANGUAGE_CODE:
					throw UseCaseError::invalidQueryParameter( self::LANGUAGE_QUERY_PARAM );
				default:
					throw new LogicException( 'unknown validation error code ' . $validationError->getCode() );
			}
		}

		$this->validateLimitAndOffset( $request );
	}

	private function validateLimitAndOffset( SimpleItemSearchRequest $request ): void {
		$limit = $request->getLimit();
		$offset = $request->getOffset();

		if ( $limit < 0 || $limit > self::MAX_LIMIT ) {
			throw UseCaseError::invalidQueryParameter( self::LIMIT_QUERY_PARAM );
		}

		if ( $offset < 0 ) {
			throw UseCaseError::invalidQueryParameter( self::OFFSET_QUERY_PARAM );
		}
	}
}
