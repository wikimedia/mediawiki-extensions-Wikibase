<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchSitelinksValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchSitelinksRequest $request ): DeserializedPatchSitelinksRequest;

}
