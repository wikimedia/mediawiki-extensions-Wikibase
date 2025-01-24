<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemStatementRequest $request ): DeserializedPatchItemStatementRequest;

}
