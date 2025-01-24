<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetSitelinks;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetSitelinksValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetSitelinksRequest $request ): DeserializedGetSitelinksRequest;

}
