<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemAliasesInLanguageValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemAliasesInLanguageRequest $request ): DeserializedGetItemAliasesInLanguageRequest;

}
