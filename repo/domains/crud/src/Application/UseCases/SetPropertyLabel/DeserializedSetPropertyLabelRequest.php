<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyLabel;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyLabelEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetPropertyLabelRequest extends DeserializedPropertyLabelEditRequest, DeserializedEditMetadataRequest {
}
