<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface AddPropertyStatementValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( AddPropertyStatementRequest $request ): DeserializedAddPropertyStatementRequest;

}
