<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\RemoveItemStatement;

use Wikibase\Repo\RestApi\Application\UseCases\DeserializedEditMetadataRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedItemIdRequest;
use Wikibase\Repo\RestApi\Application\UseCases\DeserializedStatementIdRequest;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedRemoveItemStatementRequest extends
	DeserializedItemIdRequest,
	DeserializedStatementIdRequest,
	DeserializedEditMetadataRequest
{
}
