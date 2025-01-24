<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemStatementsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemStatementsRequest $request ): DeserializedGetItemStatementsRequest;

}
