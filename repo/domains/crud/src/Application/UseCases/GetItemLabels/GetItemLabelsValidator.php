<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabels;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemLabelsValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemLabelsRequest $request ): DeserializedGetItemLabelsRequest;

}
