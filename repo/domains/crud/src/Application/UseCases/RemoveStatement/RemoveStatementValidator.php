<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveStatementRequest $request ): DeserializedRemoveStatementRequest;

}
