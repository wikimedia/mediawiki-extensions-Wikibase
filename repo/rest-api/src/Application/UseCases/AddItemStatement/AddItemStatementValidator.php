<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddItemStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddItemStatementRequest $request ): DeserializedAddItemStatementRequest;

}
