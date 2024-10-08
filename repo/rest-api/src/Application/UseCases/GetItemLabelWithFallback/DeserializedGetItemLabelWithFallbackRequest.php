<?php declare( strict_types = 1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetItemLabelWithFallback;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemLabelWithFallbackRequest extends DeserializedItemIdRequest, DeserializedLanguageCodeRequest {
}
