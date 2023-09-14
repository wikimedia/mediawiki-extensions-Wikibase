<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemoveStatementRequest extends DeserializedStatementIdRequest, DeserializedEditMetadataRequest {
}
