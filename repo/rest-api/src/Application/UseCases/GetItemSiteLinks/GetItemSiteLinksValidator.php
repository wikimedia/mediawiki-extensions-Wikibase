<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLinks;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemSiteLinksValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemSiteLinksRequest $request ): DeserializedGetItemSiteLinksRequest;

}
