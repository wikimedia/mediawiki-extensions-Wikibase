<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\CreateItem;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedCreateItemRequest
	extends DeserializedItemRequest, DeserializedEditMetadataRequest {
}
