<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyStatementRequest extends DeserializedPropertyIdRequest, DeserializedStatementIdRequest {
}
