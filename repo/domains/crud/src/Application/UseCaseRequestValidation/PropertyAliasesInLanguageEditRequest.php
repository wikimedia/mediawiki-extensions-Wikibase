<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

/**
 * @license GPL-2.0-or-later
 */
interface PropertyAliasesInLanguageEditRequest extends PropertyIdRequest, AliasLanguageCodeRequest {
	public function getAliasesInLanguage(): array;
}
