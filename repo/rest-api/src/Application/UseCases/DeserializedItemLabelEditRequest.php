<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedItemLabelEditRequest extends DeserializedItemIdRequest {
	public function getLabel(): Term;
}
