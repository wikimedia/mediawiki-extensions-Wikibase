<?php

namespace Wikibase\Lib\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Rdbms\IDatabase;

/**
 * Interface to generate the query info needed to find an entity of given ID within
 * the mediawiki page table.
 *
 * @license GPL-2.0-or-later
 */
interface PageTableEntityConditionGenerator {

	/**
	 * @param EntityId $entityIds
	 * @param IDatabase $db
	 * @return array [ string $whereCondition, array $extraTables ]
	 */
	public function getQueryInfo( array $entityIds, IDatabase $db );

}
