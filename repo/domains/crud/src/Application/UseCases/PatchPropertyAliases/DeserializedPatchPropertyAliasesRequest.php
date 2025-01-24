<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchPropertyAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPatchRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchPropertyAliasesRequest
	extends DeserializedPropertyIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
