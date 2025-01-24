<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchPropertyValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchPropertyRequest $request ): DeserializedPatchPropertyRequest;

}
