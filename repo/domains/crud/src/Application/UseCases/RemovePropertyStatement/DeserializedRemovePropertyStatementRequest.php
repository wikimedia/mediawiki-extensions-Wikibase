<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemovePropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveStatement\DeserializedRemoveStatementRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemovePropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedRemoveStatementRequest {
}
