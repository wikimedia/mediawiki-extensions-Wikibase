<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetSitelink;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedSiteIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetSitelinkRequest extends DeserializedItemIdRequest, DeserializedSiteIdRequest {
}
