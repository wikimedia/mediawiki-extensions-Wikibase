<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface LanguageCodeValidator {

	public const CODE_INVALID_LANGUAGE_CODE = 'language-code-validator-code-invalid-language-code';

	public const CONTEXT_LANGUAGE_CODE = 'language-code-validator-context-language-code';
	public const CONTEXT_PATH = 'language-code-validator-context-path';

	public function validate( string $languageCode ): ?ValidationError;

}
