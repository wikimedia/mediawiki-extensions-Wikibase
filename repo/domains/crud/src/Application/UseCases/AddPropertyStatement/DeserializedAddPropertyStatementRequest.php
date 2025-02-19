<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCases\AddPropertyStatement;

use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedEditMetadataRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedPropertyIdRequest;
use Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation\DeserializedStatementSerializationRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedAddPropertyStatementRequest
	extends DeserializedPropertyIdRequest, DeserializedStatementSerializationRequest, DeserializedEditMetadataRequest {
}
