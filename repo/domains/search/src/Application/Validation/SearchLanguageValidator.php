<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Application\Validation;

/**
 * @license GPL-2.0-or-later
 */
interface SearchLanguageValidator {

	public const CODE_INVALID_LANGUAGE_CODE = 'search-language-validator-code-invalid-language-code';

	public const CONTEXT_LANGUAGE_CODE = 'search-language-validator-context-language-code';

	public function validate( string $languageCode ): ?ValidationError;

}
