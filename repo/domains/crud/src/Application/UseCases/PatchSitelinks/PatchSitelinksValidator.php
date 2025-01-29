<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchSitelinksValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchSitelinksRequest $request ): DeserializedPatchSitelinksRequest;

}
