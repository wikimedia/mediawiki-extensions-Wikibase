<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\PatchItemLabels;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPatchRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchItemLabelsRequest extends DeserializedItemIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
