<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyDescriptions;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchPropertyDescriptionsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchPropertyDescriptionsRequest $request ): DeserializedPatchPropertyDescriptionsRequest;

}
