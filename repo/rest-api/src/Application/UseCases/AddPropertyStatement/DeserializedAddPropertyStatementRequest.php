<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementSerializationRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddPropertyStatementRequest
	extends DeserializedPropertyIdRequest, DeserializedStatementSerializationRequest, DeserializedEditMetadataRequest {
}
