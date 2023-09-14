<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\PatchStatement\DeserializedPatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchPropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedPatchStatementRequest {
}
