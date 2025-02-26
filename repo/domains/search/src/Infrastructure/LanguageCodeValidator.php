<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Infrastructure;

use ValueValidators\ValueValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\SearchLanguageValidator;
use Wikibase\Repo\Domains\Search\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class LanguageCodeValidator implements SearchLanguageValidator {

	private ValueValidator $validator;

	public function __construct( ValueValidator $validator ) {
		$this->validator = $validator;
	}

	public function validate( string $languageCode ): ?ValidationError {
		$result = $this->validator->validate( $languageCode );
		if ( !$result->isValid() ) {
			return new ValidationError(
				self::CODE_INVALID_LANGUAGE_CODE,
				[ self::CONTEXT_LANGUAGE_CODE => $languageCode ]
			);
		}

		return null;
	}

}
