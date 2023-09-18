<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescription;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemDescriptionRequest $request ): DeserializedGetItemDescriptionRequest;

}
