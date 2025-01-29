<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\GetStatement\DeserializedGetStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemStatementRequest extends DeserializedItemIdRequest, DeserializedGetStatementRequest {
}
