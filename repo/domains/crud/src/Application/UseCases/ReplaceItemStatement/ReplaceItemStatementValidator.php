<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface ReplaceItemStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ReplaceItemStatementRequest $request ): DeserializedReplaceItemStatementRequest;

}
