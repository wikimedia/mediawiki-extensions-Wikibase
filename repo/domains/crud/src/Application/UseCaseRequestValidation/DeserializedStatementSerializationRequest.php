<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Application\UseCaseRequestValidation;

use Wikibase\DataModel\Statement\Statement;

/**
 * @license GPL-2.0-or-later
 */
interface DeserializedStatementSerializationRequest {
	public function getStatement(): Statement;
}
