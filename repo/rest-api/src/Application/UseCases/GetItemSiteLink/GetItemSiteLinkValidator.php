<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemSiteLink;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemSiteLinkValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemSiteLinkRequest $request ): DeserializedGetItemSiteLinkRequest;

}
