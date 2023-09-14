<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\DeserializedPatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchItemStatementRequest extends DeserializedItemIdRequest, DeserializedPatchStatementRequest {
}
