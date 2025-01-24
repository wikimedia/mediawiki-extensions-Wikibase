<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchStatementRequest $request ): DeserializedPatchStatementRequest;

}
