<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\GetStatement\DeserializedGetStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemStatementRequest extends DeserializedItemIdRequest, DeserializedGetStatementRequest {
}
