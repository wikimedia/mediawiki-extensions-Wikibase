<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface CreateItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreateItemRequest $request ): DeserializedCreateItemRequest;

}
