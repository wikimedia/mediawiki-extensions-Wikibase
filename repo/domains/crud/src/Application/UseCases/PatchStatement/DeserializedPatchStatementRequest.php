<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchStatementRequest
	extends DeserializedStatementIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
