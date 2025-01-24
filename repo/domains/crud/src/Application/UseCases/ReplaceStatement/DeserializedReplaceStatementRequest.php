<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedStatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedStatementSerializationRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplaceStatementRequest
	extends DeserializedStatementIdRequest, DeserializedStatementSerializationRequest, DeserializedEditMetadataRequest {
}
