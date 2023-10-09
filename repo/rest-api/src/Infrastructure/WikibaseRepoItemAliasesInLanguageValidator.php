<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Infrastructure;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\RestApi\Application\Validation\ItemAliasesInLanguageValidator;
use Wikibase\Repo\RestApi\Application\Validation\ValidationError;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class WikibaseRepoItemAliasesInLanguageValidator implements ItemAliasesInLanguageValidator {

	private TermValidatorFactory $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function validate( AliasGroup $aliasesInLanguage ): ?ValidationError {
		foreach ( $aliasesInLanguage->getAliases() as $alias ) {
			$validationError = $this->validateAliasText( $alias );
			if ( $validationError !== null ) {
				return $validationError;
			}
		}

		return null;
	}

	private function validateAliasText( string $aliasText ): ?ValidationError {
		$result = $this->termValidatorFactory->getAliasValidator()->validate( $aliasText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[ 0 ];
			switch ( $error->getCode() ) {
				case 'alias-too-long':
					return new ValidationError(
						ItemAliasesInLanguageValidator::CODE_TOO_LONG,
						[
							ItemAliasesInLanguageValidator::CONTEXT_VALUE => $aliasText,
							ItemAliasesInLanguageValidator::CONTEXT_LIMIT => $error->getParameters()[ 0 ],
						]
					);
				default:
					return new ValidationError(
						ItemAliasesInLanguageValidator::CODE_INVALID,
						[ ItemAliasesInLanguageValidator::CONTEXT_VALUE => $aliasText ]
					);
			}
		}

		return null;
	}

}
