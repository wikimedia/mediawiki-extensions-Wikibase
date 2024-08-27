<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\AliasGroup;

/**
 * @license GPL-2.0-or-later
 */
interface AliasesInLanguageValidator {

	public const CODE_INVALID = 'aliases-in-language-validator-code-invalid-alias';
	public const CODE_TOO_LONG = 'aliases-in-language-validator-code-alias-too-long';

	public const CONTEXT_VALUE = 'aliases-in-language-validator-context-value';
	public const CONTEXT_LIMIT = 'aliases-in-language-validator-context-character-limit';
	public const CONTEXT_LANGUAGE = 'aliases-in-language-validator-context-language';
	public const CONTEXT_PATH = 'aliases-in-language-validator-context-path';

	public function validate( AliasGroup $aliasesInLanguage, string $basePath ): ?ValidationError;

}
