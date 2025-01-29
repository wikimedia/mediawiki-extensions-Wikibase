<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchSitelinks;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPatchRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchSitelinksRequest
	extends DeserializedItemIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
