<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Term\Term;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedItemLabelEditRequest extends DeserializedItemIdRequest {
	public function getItemLabel(): Term;
}
