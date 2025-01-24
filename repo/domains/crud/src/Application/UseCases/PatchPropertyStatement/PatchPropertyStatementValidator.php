<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchPropertyStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchPropertyStatementRequest $request ): DeserializedPatchPropertyStatementRequest;

}
