<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\AddItemAliases;

use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCaseRequestValidation\DeserializedItemAliasesEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddItemAliasesRequest
	extends DeserializedItemAliasesEditRequest, DeserializedEditMetadataRequest {
}
