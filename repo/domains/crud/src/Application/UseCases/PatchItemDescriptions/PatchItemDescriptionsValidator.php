<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemDescriptions;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemDescriptionsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemDescriptionsRequest $request ): DeserializedPatchItemDescriptionsRequest;

}
