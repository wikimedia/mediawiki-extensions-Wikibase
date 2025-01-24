<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\SetItemLabel;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedItemLabelEditRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedSetItemLabelRequest extends DeserializedItemLabelEditRequest, DeserializedEditMetadataRequest {
}
