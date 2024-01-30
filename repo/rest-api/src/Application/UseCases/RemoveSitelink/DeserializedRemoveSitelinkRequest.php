<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveSitelink;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedSiteIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemoveSitelinkRequest
	extends DeserializedItemIdRequest, DeserializedSiteIdRequest, DeserializedEditMetadataRequest {
}
