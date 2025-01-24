<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemRequest $request ): DeserializedGetItemRequest;

}
