<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItemStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\DeserializedPatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchItemStatementRequest extends DeserializedItemIdRequest, DeserializedPatchStatementRequest {
}
