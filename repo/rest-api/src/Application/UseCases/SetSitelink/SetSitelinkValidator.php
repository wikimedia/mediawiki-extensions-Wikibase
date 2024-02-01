<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetSitelink;

/**
 * @license GPL-2.0-or-later
 */
interface SetSitelinkValidator {
	public function validateAndDeserialize( SetSitelinkRequest $request ): DeserializedSetSitelinkRequest;
}
