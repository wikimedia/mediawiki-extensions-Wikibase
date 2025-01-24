<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchProperty;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchPropertyValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchPropertyRequest $request ): DeserializedPatchPropertyRequest;

}
