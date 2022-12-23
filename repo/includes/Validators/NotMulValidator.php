<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Validators;

use MediaWiki\Languages\LanguageNameUtils;
use RequestContext;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * Validator ensuring the "mul" language code is not being used for descriptions.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch <mail@mariushoch.de>
 */
class NotMulValidator implements ValueValidator {

	private LanguageNameUtils $languageNameUtils;

	public function __construct( LanguageNameUtils $languageNameUtils ) {
		$this->languageNameUtils = $languageNameUtils;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $value The value to validate
	 *
	 * @return Result
	 */
	public function validate( $value ): Result {
		if ( $value === 'mul' ) {
			$mulLanguageName = $this->languageNameUtils->getLanguageName(
				'mul',
				// Use the request language, like Message does.
				RequestContext::getMain()->getLanguage()->getCode()
			);

			return Result::newError( [
				Error::newError(
					'The language code “mul” (multiple languages) can only be used for labels and aliases, not for descriptions.',
					null,
					'no-mul-descriptions',
					[ $mulLanguageName ]
				),
			] );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
