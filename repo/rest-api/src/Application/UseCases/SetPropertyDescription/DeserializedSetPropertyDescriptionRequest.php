<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyDescriptionEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetPropertyDescriptionRequest extends DeserializedPropertyDescriptionEditRequest, DeserializedEditMetadataRequest {
}
