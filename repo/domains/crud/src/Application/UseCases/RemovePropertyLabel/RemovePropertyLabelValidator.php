<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface RemovePropertyLabelValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( RemovePropertyLabelRequest $request ): DeserializedRemovePropertyLabelRequest;

}
