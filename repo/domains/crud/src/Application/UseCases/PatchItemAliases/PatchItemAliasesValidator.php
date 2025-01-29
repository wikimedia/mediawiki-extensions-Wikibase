<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemAliases;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemAliasesValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemAliasesRequest $request ): DeserializedPatchItemAliasesRequest;

}
