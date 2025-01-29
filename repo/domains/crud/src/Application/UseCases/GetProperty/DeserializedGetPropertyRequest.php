<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\GetProperty;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyFieldsRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedGetPropertyRequest extends DeserializedPropertyIdRequest, DeserializedPropertyFieldsRequest {
}
