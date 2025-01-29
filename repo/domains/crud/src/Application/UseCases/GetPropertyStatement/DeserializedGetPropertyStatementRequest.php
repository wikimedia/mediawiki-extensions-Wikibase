<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedStatementIdRequest {
}
