<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement\DeserializedPatchStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchPropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedPatchStatementRequest {
}
