<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedPropertyAliasesInLanguageEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddPropertyAliasesInLanguageRequest
	extends DeserializedPropertyAliasesInLanguageEditRequest, DeserializedEditMetadataRequest {
}
