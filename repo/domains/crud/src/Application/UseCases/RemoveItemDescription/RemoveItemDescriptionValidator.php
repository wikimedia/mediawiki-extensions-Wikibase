<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveItemDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveItemDescriptionRequest $request ): DeserializedRemoveItemDescriptionRequest;

}
