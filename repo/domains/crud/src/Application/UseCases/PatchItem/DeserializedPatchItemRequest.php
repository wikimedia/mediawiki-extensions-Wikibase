<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\PatchItem;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPatchRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedPatchItemRequest extends DeserializedItemIdRequest, DeserializedPatchRequest, DeserializedEditMetadataRequest {
}
