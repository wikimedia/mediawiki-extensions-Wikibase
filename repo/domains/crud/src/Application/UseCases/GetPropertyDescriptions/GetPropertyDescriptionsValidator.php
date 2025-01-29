<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptions;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyDescriptionsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetPropertyDescriptionsRequest $request ): DeserializedGetPropertyDescriptionsRequest;

}
