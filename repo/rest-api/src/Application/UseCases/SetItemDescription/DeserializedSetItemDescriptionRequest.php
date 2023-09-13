<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemDescription;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemDescriptionEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetItemDescriptionRequest extends DeserializedItemDescriptionEditRequest, DeserializedEditMetadataRequest {
}
