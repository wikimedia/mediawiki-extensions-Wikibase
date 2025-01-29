<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemDescriptionWithFallback;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

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
