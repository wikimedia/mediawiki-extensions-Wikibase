<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
interface StatementWriteModelRetriever {

	public function getStatementWriteModel( StatementGuid $statementId ): ?Statement;

}
