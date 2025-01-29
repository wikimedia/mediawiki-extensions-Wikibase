<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetPropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyDescriptionRequest extends DeserializedPropertyIdRequest, DeserializedLanguageCodeRequest {
}
