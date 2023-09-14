<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementSerializationRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplaceStatementRequest
	extends DeserializedStatementIdRequest, DeserializedStatementSerializationRequest, DeserializedEditMetadataRequest {
}
