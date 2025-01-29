<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItem;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemFieldsRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemRequest extends DeserializedItemIdRequest, DeserializedItemFieldsRequest {
}
