<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetStatementRequest $request ): DeserializedGetStatementRequest;

}
