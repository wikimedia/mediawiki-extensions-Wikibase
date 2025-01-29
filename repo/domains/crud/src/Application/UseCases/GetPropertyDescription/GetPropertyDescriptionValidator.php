<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyDescriptionRequest $request ): DeserializedGetPropertyDescriptionRequest;

}
