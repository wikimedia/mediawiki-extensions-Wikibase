<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\ReplacePropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\ReplaceStatement\DeserializedReplaceStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedReplacePropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedReplaceStatementRequest {
}
