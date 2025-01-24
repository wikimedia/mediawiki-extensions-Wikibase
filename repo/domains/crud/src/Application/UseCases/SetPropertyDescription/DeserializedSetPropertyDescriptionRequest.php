<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyDescription;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyDescriptionEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetPropertyDescriptionRequest extends DeserializedPropertyDescriptionEditRequest, DeserializedEditMetadataRequest {
}
