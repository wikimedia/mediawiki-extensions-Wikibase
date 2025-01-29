<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface ReplaceStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ReplaceStatementRequest $request ): DeserializedReplaceStatementRequest;

}
