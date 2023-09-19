<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\RemoveStatement\DeserializedRemoveStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemovePropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedRemoveStatementRequest {
}
