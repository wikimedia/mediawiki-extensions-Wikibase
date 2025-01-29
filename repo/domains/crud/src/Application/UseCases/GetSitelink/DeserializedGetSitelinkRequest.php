<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedSiteIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetSitelinkRequest extends DeserializedItemIdRequest, DeserializedSiteIdRequest {
}
