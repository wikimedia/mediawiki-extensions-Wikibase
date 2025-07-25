<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\UseCases\PropertyPrefixSearch;

use LogicException;
use Wikibase\Repo\Domains\Search\Application\UseCases\UseCaseError;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;

/**
 * @license GPL-2.0-or-later
 */
class PropertyPrefixSearchValidator {

	public const LANGUAGE_QUERY_PARAM = 'language';
	public const LIMIT_QUERY_PARAM = 'limit';
	public const OFFSET_QUERY_PARAM = 'offset';

	private const MAX_LIMIT = 500;

	public function __construct( private SearchLanguageValidator $languageValidator ) {
	}

	/**
	 * @throws UseCaseError
	 */
	public function validate( PropertyPrefixSearchRequest $request ): void {
		$validationError = $this->languageValidator->validate( $request->language );

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

	private function validateLimitAndOffset( PropertyPrefixSearchRequest $request ): void {
		if ( $request->limit < 0 || $request->limit > self::MAX_LIMIT ) {
			throw UseCaseError::invalidQueryParameter( self::LIMIT_QUERY_PARAM );
		}

		if ( $request->offset < 0 ) {
			throw UseCaseError::invalidQueryParameter( self::OFFSET_QUERY_PARAM );
		}
	}
}
