<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\RemoveSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedSiteIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemoveSitelinkRequest
	extends DeserializedItemIdRequest, DeserializedSiteIdRequest, DeserializedEditMetadataRequest {
}
