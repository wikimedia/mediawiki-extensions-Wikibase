<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveStatementRequest $request ): DeserializedRemoveStatementRequest;

}
