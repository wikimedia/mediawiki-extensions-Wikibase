<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface SetItemLabelValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( SetItemLabelRequest $request ): DeserializedSetItemLabelRequest;

}
