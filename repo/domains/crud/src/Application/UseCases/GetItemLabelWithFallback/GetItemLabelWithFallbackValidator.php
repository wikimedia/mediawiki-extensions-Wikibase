<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabelWithFallback;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemLabelWithFallbackValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemLabelWithFallbackRequest $request ): DeserializedGetItemLabelWithFallbackRequest;

}
