<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyAliasesInLanguageEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddPropertyAliasesInLanguageRequest
	extends DeserializedPropertyAliasesInLanguageEditRequest, DeserializedEditMetadataRequest {
}
