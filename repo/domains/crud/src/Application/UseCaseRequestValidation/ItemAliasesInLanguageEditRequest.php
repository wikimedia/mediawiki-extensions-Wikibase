<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

/**
 * @license GPL-2.0-or-later
 */
interface ItemAliasesInLanguageEditRequest extends ItemIdRequest, AliasLanguageCodeRequest {
	public function getAliasesInLanguage(): array;
}
