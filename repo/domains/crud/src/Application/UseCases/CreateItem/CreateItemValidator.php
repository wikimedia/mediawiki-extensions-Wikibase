<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface CreateItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( CreateItemRequest $request ): DeserializedCreateItemRequest;

}
