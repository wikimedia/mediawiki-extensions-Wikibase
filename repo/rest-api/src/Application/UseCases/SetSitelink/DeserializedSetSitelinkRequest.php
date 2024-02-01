<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetSitelink;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedSiteIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetSitelinkRequest
	extends DeserializedItemIdRequest, DeserializedSiteIdRequest, DeserializedEditMetadataRequest {

}
