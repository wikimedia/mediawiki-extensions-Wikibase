<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel;

/**
 * @license GPL-2.0-or-later
 */
interface SetPropertyLabelValidator {

	public function validateAndDeserialize( SetPropertyLabelRequest $request ): DeserializedSetPropertyLabelRequest;

}
