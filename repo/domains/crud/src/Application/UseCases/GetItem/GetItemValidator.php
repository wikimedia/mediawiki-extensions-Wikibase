<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemRequest $request ): DeserializedGetItemRequest;

}
