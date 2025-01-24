<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescriptionWithFallback;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetPropertyDescriptionWithFallbackValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize(
		GetPropertyDescriptionWithFallbackRequest $request
	): DeserializedGetPropertyDescriptionWithFallbackRequest;
}
