<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedStatementSerializationRequest {
	public function getStatement(): Statement;
}
