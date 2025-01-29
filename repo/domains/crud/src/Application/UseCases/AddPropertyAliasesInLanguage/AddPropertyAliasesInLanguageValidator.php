<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddPropertyAliasesInLanguageValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddPropertyAliasesInLanguageRequest $request ): DeserializedAddPropertyAliasesInLanguageRequest;

}
