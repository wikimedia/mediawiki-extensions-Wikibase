<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemovePropertyDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemovePropertyDescriptionRequest $request ): DeserializedRemovePropertyDescriptionRequest;

}
