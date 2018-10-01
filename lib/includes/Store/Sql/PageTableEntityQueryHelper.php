<?php

namespace Wikibase\Lib\Store\Sql;

use Traversable;
use Wikibase\DataModel\Entity\EntityId;
use Wikimedia\Rdbms\IDatabase;

/**
 * Interface to generate the query info needed to find an entity of given ID within
 * the mediawiki page table and also map resulting rows back to the entity IDs they
 * relate to.
 *
 * @license GPL-2.0-or-later
 */
interface PageTableEntityQueryHelper {

	/**
	 * @param EntityId $entityIds
	 * @param IDatabase $db
	 * @return array [ string $whereCondition, array $extraTables ]
	 */
	public function getQueryInfo( array $entityIds, IDatabase $db );

	/**
	 * @param Traversable $rows
	 * @return array of rows with keys of their entity ID serializations
	 */
	public function mapRowsToEntityIds( Traversable $rows );

}
