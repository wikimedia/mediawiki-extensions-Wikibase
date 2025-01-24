<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemDescription;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveItemDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveItemDescriptionRequest $request ): DeserializedRemoveItemDescriptionRequest;

}
