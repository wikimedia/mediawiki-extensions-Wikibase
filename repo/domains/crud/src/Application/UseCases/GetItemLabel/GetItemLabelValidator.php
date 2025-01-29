<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabel;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemLabelValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemLabelRequest $request ): DeserializedGetItemLabelRequest;

}
