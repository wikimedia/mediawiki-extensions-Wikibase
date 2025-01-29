<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetItem;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetItemRequest extends DeserializedItemIdRequest, DeserializedItemFieldsRequest {
}
