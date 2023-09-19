<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemStatements;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdFilterRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemStatementsRequest extends DeserializedItemIdRequest, DeserializedPropertyIdFilterRequest {
}
