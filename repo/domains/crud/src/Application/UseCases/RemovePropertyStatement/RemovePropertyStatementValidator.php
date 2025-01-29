<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemovePropertyStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemovePropertyStatementRequest $request ): DeserializedRemovePropertyStatementRequest;

}
