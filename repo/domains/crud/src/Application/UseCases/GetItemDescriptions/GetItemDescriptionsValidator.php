<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptions;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemDescriptionsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemDescriptionsRequest $request ): DeserializedGetItemDescriptionsRequest;

}
