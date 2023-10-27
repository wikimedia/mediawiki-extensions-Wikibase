<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddItemAliasesValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddItemAliasesRequest $request ): DeserializedAddItemAliasesRequest;

}
