<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\DeserializedRemoveStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemoveItemStatementRequest extends DeserializedItemIdRequest, DeserializedRemoveStatementRequest {
}
