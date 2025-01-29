<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddPropertyAliasesInLanguageValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddPropertyAliasesInLanguageRequest $request ): DeserializedAddPropertyAliasesInLanguageRequest;

}
