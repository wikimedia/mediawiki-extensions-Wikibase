<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\ReplaceStatement\DeserializedReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplacePropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedReplaceStatementRequest {
}
