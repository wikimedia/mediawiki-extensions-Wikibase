<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPatchRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchStatementRequest
	extends DeserializedStatementIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
