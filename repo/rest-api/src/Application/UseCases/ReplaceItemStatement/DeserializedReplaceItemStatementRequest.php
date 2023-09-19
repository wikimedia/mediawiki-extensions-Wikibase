<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\DeserializedReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplaceItemStatementRequest extends DeserializedItemIdRequest, DeserializedReplaceStatementRequest {
}
