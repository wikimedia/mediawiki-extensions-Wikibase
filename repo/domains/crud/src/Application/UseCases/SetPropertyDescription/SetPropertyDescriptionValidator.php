<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface SetPropertyDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SetPropertyDescriptionRequest $request ): DeserializedSetPropertyDescriptionRequest;

}
