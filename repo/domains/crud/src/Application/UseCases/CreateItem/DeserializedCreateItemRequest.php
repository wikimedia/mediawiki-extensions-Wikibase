<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\CreateItem;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedCreateItemRequest
	extends DeserializedItemRequest, DeserializedEditMetadataRequest {
}
