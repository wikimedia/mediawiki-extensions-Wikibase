<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Infrastructure;

use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\Repo\Domains\Crud\Application\Validation\AliasesInLanguageValidator;
use Wikibase\Repo\Domains\Crud\Application\Validation\ValidationError;
use Wikibase\Repo\Validators\TermValidatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermValidatorFactoryAliasesInLanguageValidator implements AliasesInLanguageValidator {

	private TermValidatorFactory $termValidatorFactory;

	public function __construct( TermValidatorFactory $termValidatorFactory ) {
		$this->termValidatorFactory = $termValidatorFactory;
	}

	public function validate( AliasGroup $aliasesInLanguage, string $basePath ): ?ValidationError {
		foreach ( $aliasesInLanguage->getAliases() as $index => $alias ) {
			$validationError = $this->validateAliasText( $alias, "$basePath/$index" );
			if ( $validationError !== null ) {
				return $validationError;
			}
		}

		return null;
	}

	private function validateAliasText( string $aliasText, string $path ): ?ValidationError {
		$result = $this->termValidatorFactory->getAliasValidator()->validate( $aliasText );
		if ( !$result->isValid() ) {
			$error = $result->getErrors()[ 0 ];
			switch ( $error->getCode() ) {
				case 'alias-too-long':
					return new ValidationError(
						AliasesInLanguageValidator::CODE_TOO_LONG,
						[
							AliasesInLanguageValidator::CONTEXT_VALUE => $aliasText,
							AliasesInLanguageValidator::CONTEXT_LIMIT => $error->getParameters()[ 0 ],
							AliasesInLanguageValidator::CONTEXT_PATH => $path,
						]
					);
				default:
					return new ValidationError(
						AliasesInLanguageValidator::CODE_INVALID,
						[
							AliasesInLanguageValidator::CONTEXT_VALUE => $aliasText,
							AliasesInLanguageValidator::CONTEXT_PATH => $path,
						]
					);
			}
		}

		return null;
	}

}
