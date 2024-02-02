<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveSitelink;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveSitelinkValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveSitelinkRequest $request ): DeserializedRemoveSitelinkRequest;

}
