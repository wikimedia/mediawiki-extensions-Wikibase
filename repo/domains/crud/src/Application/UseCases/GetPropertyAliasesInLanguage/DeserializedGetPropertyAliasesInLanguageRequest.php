<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyAliasesInLanguageRequest extends DeserializedPropertyIdRequest, DeserializedLanguageCodeRequest {

}
