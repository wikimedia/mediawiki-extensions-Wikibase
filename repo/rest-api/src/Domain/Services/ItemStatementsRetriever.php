<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Services;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;

/**
 * @license GPL-2.0-or-later
 */
interface ItemStatementsRetriever {

	public function getStatements( ItemId $itemId ): ?StatementList;

}
