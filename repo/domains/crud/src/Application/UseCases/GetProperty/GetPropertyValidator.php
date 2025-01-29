<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyRequest $request ): DeserializedGetPropertyRequest;

}
