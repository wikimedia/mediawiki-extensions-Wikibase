<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveItemStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveItemStatementRequest $request ): DeserializedRemoveItemStatementRequest;

}
