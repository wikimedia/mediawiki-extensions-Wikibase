<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use ValueValidators\ValueValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\LabelLanguageCodeValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;

/**
 * @license GPL-2.0-or-later
 */
class ValueValidatorLanguageCodeValidator
	implements LabelLanguageCodeValidator, DescriptionLanguageCodeValidator, AliasLanguageCodeValidator {

	private ValueValidator $validator;

	public function __construct( ValueValidator $validator ) {
		$this->validator = $validator;
	}

	public function validate( string $languageCode, string $basePath = '' ): ?ValidationError {
		$result = $this->validator->validate( $languageCode );
		if ( !$result->isValid() ) {
			return new ValidationError(
				self::CODE_INVALID_LANGUAGE_CODE,
				[ self::CONTEXT_LANGUAGE_CODE => $languageCode, self::CONTEXT_PATH => $basePath ]
			);
		}

		return null;
	}

}
