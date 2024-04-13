<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Validation;

use Wikibase\DataModel\Term\AliasGroup;

/**
 * @license GPL-2.0-or-later
 */
interface AliasesInLanguageValidator {

	public const CODE_INVALID = 'invalid-alias';
	public const CODE_TOO_LONG = 'alias-too-long';

	public const CONTEXT_VALUE = 'value';
	public const CONTEXT_LIMIT = 'character-limit';
	public const CONTEXT_LANGUAGE = 'language';
	public const CONTEXT_PATH = 'path';

	public function validate( AliasGroup $aliasesInLanguage ): ?ValidationError;

}
