<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddItemAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemAliasesInLanguageEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddItemAliasesInLanguageRequest
	extends DeserializedItemAliasesInLanguageEditRequest, DeserializedEditMetadataRequest {
}
