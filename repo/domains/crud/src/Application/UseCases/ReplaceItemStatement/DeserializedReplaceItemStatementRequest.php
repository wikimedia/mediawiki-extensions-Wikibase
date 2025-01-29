<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\DeserializedReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplaceItemStatementRequest extends DeserializedItemIdRequest, DeserializedReplaceStatementRequest {
}
