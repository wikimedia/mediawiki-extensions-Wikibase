<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface PatchItemLabelsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( PatchItemLabelsRequest $request ): DeserializedPatchItemLabelsRequest;

}
