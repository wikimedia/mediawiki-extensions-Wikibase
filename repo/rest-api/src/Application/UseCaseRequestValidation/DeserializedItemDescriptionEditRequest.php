<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedItemDescriptionEditRequest extends DeserializedItemIdRequest {
	public function getDescription(): Term;
}
