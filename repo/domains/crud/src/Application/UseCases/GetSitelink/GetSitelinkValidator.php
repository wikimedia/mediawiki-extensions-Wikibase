<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetSitelinkValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetSitelinkRequest $request ): DeserializedGetSitelinkRequest;

}
