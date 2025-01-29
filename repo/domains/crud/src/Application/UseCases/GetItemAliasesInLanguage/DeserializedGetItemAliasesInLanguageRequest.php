<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItemAliasesInLanguage;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedLanguageCodeRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemAliasesInLanguageRequest extends DeserializedItemIdRequest, DeserializedLanguageCodeRequest {

}
