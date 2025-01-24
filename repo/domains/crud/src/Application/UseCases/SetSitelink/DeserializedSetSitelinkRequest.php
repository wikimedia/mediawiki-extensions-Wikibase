<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetSitelink;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedSitelinkEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetSitelinkRequest
	extends DeserializedSitelinkEditRequest, DeserializedEditMetadataRequest {
}
