<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemAliases;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemAliasesValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemAliasesRequest $request ): DeserializedPatchItemAliasesRequest;

}
