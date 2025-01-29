<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface SetItemDescriptionValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SetItemDescriptionRequest $request ): DeserializedSetItemDescriptionRequest;

}
