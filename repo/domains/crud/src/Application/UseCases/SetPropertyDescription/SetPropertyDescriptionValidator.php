<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface SetPropertyDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SetPropertyDescriptionRequest $request ): DeserializedSetPropertyDescriptionRequest;

}
