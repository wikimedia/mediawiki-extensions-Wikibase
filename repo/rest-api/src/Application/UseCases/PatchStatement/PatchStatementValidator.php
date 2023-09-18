<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchStatementRequest $request ): DeserializedPatchStatementRequest;

}
