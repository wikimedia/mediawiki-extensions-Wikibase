<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemDescriptionEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetItemDescriptionRequest extends DeserializedItemDescriptionEditRequest, DeserializedEditMetadataRequest {
}
