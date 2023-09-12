<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetItemLabel;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemLabelEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetItemLabelRequest extends DeserializedItemLabelEditRequest, DeserializedEditMetadataRequest {
}
