<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemLabel;

use Wikibase\Repo\Domains\Crud\Application\UseCases\UseCaseError;

/**
 * @license GPL-2.0-or-later
 */
interface GetItemLabelValidator {

	/**
	 * @throws UseCaseError
	 */
	public function validateAndDeserialize( GetItemLabelRequest $request ): DeserializedGetItemLabelRequest;

}
