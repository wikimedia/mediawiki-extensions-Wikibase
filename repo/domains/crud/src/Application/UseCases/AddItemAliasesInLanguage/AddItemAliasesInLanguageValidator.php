<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddItemAliasesInLanguageValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddItemAliasesInLanguageRequest $request ): DeserializedAddItemAliasesInLanguageRequest;

}
