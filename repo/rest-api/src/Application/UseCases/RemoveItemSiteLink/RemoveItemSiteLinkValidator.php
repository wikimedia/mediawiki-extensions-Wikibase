<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemSiteLink;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveItemSiteLinkValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveItemSiteLinkRequest $request ): DeserializedRemoveItemSiteLinkRequest;

}
