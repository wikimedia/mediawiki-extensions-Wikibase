<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedStatementSerializationRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddItemStatementRequest
	extends DeserializedItemIdRequest, DeserializedStatementSerializationRequest, DeserializedEditMetadataRequest {
}
