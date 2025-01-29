<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemoveSitelinkValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemoveSitelinkRequest $request ): DeserializedRemoveSitelinkRequest;

}
