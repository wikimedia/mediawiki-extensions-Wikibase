<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
class PropertyLabelEditRequestValidatingDeserializer {

	public function validateAndDeserialize( PropertyLabelEditRequest $request ): Term {
		// TODO: validation
		return new Term( $request->getLanguageCode(), $request->getLabel() );
	}

}
