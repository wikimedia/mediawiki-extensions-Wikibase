<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemovePropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemovePropertyDescriptionRequest
	extends DeserializedPropertyIdRequest, DeserializedLanguageCodeRequest, DeserializedEditMetadataRequest {

}
