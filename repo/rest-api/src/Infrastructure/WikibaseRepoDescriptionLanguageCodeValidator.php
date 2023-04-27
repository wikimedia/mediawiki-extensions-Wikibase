<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\Repo\RestApi\Application\Validation\DescriptionLanguageCodeValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoDescriptionLanguageCodeValidator implements DescriptionLanguageCodeValidator {

	private TermValidatorFactory $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function validate( string $language ): ?ValidationError {
		$result = $this->termValidatorFactory->getDescriptionLanguageValidator()->validate( $language );
		if ( !$result->isValid() ) {
			return new ValidationError(
				self::CODE_INVALID_LANGUAGE,
				[ self::CONTEXT_LANGUAGE => $language ]
			);
		}

		return null;
	}

}
