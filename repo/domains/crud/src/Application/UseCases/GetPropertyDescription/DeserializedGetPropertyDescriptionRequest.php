<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyDescriptionRequest extends DeserializedPropertyIdRequest, DeserializedLanguageCodeRequest {
}
