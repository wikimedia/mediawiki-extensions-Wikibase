<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetSitelink;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedSitelinkEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetSitelinkRequest
	extends DeserializedSitelinkEditRequest, DeserializedEditMetadataRequest {
}
