<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemLabelsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemLabelsRequest $request ): DeserializedPatchItemLabelsRequest;

}
