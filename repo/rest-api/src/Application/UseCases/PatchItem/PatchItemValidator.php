<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItem;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemRequest $request ): DeserializedPatchItemRequest;

}
