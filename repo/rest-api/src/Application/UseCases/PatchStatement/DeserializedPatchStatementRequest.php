<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPatchRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchStatementRequest
	extends DeserializedStatementIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
