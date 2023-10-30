<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliasesInLanguage;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemAliasesInLanguageEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddItemAliasesInLanguageRequest
	extends DeserializedItemAliasesInLanguageEditRequest, DeserializedEditMetadataRequest {
}
