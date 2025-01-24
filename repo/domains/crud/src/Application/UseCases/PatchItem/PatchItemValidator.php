<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemRequest $request ): DeserializedPatchItemRequest;

}
