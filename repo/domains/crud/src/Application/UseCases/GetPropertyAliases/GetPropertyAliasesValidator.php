<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyAliases;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyAliasesValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyAliasesRequest $request ): DeserializedGetPropertyAliasesRequest;

}
