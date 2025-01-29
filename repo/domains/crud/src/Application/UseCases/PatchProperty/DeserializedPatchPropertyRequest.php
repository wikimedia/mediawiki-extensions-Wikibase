<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchProperty;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPatchRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchPropertyRequest
	extends DeserializedPropertyIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
