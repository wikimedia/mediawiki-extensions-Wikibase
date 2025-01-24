<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSitelinkEditRequest extends DeserializedItemIdRequest, DeserializedSiteIdRequest {
	public function getSitelink(): SiteLink;
}
