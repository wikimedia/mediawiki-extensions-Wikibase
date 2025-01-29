<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface ReplacePropertyStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( ReplacePropertyStatementRequest $request ): DeserializedReplacePropertyStatementRequest;

}
