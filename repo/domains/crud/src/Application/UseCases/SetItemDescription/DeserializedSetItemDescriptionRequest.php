<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemDescriptionEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetItemDescriptionRequest extends DeserializedItemDescriptionEditRequest, DeserializedEditMetadataRequest {
}
