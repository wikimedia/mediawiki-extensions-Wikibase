<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedStatementIdRequest {
}
