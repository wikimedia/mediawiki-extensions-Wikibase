<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetPropertyLabel;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyLabelEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetPropertyLabelRequest extends DeserializedPropertyLabelEditRequest, DeserializedEditMetadataRequest {
}
