<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Crud\Domain\Services;

use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Repo\Domains\Crud\Domain\ReadModel\Statement;

/**
 * @license GPL-2.0-or-later
 */
interface StatementRetriever {

	public function getStatement( StatementGuid $statementId ): ?Statement;

}
