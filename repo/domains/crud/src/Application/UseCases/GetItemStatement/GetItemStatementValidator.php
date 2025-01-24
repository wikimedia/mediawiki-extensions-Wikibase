<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemStatementRequest $request ): DeserializedGetItemStatementRequest;

}
