<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyStatementsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyStatementsRequest $request ): DeserializedGetPropertyStatementsRequest;

}
