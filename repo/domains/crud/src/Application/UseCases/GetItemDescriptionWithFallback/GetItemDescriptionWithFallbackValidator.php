<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemDescriptionWithFallback;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemDescriptionWithFallbackValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		GetItemDescriptionWithFallbackRequest $request
	): DeserializedGetItemDescriptionWithFallbackRequest;

}
