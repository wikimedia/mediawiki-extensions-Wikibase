<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatements;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdFilterRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemStatementsRequest extends DeserializedItemIdRequest, DeserializedPropertyIdFilterRequest {
}
