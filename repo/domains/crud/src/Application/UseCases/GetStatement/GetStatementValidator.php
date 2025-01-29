<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetStatementRequest $request ): DeserializedGetStatementRequest;

}
