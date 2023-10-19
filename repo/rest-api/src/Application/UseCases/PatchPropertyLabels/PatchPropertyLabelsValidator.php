<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyLabels;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchPropertyLabelsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchPropertyLabelsRequest $request ): DeserializedPatchPropertyLabelsRequest;

}
