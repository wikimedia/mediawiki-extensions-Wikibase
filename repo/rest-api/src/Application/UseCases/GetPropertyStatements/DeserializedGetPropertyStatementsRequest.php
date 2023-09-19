<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdFilterRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyStatementsRequest extends DeserializedPropertyIdRequest, DeserializedPropertyIdFilterRequest {
}
