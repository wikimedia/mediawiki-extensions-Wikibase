<?php

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MWException;
use Traversable;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\LBFactory;

/**
 * Helper class for updating the wbc_entity_usage table.
 * This is used internally by SqlUsageTracker.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 * @internal
 */
class EntityUsageTable {

	const DEFAULT_TABLE_NAME = 'wbc_entity_usage';

	/**
	 * INSERTs are supposed to be done in much larger batches than SELECTs or DELETEs, per the DBA.
	 * About 1000 was suggested. Given the default batch size is 100, a factor of 5 seems to be a
	 * good compromise.
	 */
	const INSERT_BATCH_SIZE_FACTOR = 5;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var IDatabase
	 */
	private $writeConnection;

	/**
	 * @var IDatabase
	 */
	private $readConnection;

	/**
	 * @var LBFactory
	 */
	private $loadBalancerFactory;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @param EntityIdParser $idParser
	 * @param IDatabase $writeConnection
	 * @param int $batchSize Batch size for database queries on the entity usage table, including
	 *  INSERTs, SELECTs, and DELETEs. Defaults to 100.
	 * @param string|null $tableName defaults to wbc_entity_usage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $idParser,
		IDatabase $writeConnection,
		$batchSize = 100,
		$tableName = null
	) {
		if ( !is_int( $batchSize ) || $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( !is_string( $tableName ) && $tableName !== null ) {
			throw new InvalidArgumentException( '$tableName must be a string or null' );
		}

		$this->idParser = $idParser;
		$this->writeConnection = $writeConnection;
		$this->batchSize = $batchSize;
		$this->tableName = $tableName ?: self::DEFAULT_TABLE_NAME;

		//TODO: Inject
		$this->loadBalancerFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$this->readConnection = $this->loadBalancerFactory->getMainLB()->getConnection( DB_REPLICA );
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int[] affected row ids
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	private function getAffectedRowIds( $pageId, array $usages ) {
		$usageConditions = [];
		$db = $this->writeConnection;

		foreach ( $usages as $usage ) {
			$usageConditions[] = $db->makeList( [
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
			], LIST_AND );
		}

		// Collect affected row IDs, so we can use them for an
		// efficient update query on the master db.
		$rowIds = [];
		foreach ( array_chunk( $usageConditions, $this->batchSize ) as $usageConditionChunk ) {
			$where = [
				'eu_page_id' => (int)$pageId,
				$db->makeList( $usageConditionChunk, LIST_OR )
			];

			$rowIds = array_merge(
				$this->getPrimaryKeys( $where, __METHOD__ ),
				$rowIds
			);
		}

		return $rowIds;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return array[]
	 */
	private function makeUsageRows( $pageId, array $usages ) {
		$rows = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = [
				'eu_page_id' => (int)$pageId,
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization()
			];
		}

		return $rows;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return int The number of entries added
	 */
	public function addUsages( $pageId, array $usages ) {
		if ( empty( $usages ) ) {
			return 0;
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages ),
			$this->batchSize * self::INSERT_BATCH_SIZE_FACTOR
		);

		$c = 0;

		foreach ( $batches as $rows ) {
			$this->writeConnection->insert( $this->tableName, $rows, __METHOD__, [ 'IGNORE' ] );
			$c += $this->writeConnection->affectedRows();

			// Wait for all database replicas to be updated, but only for the affected client wiki. The
			// "domain" argument is documented at ILBFactory::waitForReplication.
			$this->loadBalancerFactory->waitForReplication( [ 'domain' => wfWikiID() ] );
		}

		return $c;
	}

	/**
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[] EntityUsage identity string => EntityUsage
	 */
	public function queryUsages( $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$res = $this->readConnection->select(
			$this->tableName,
			[ 'eu_aspect', 'eu_entity_id' ],
			[ 'eu_page_id' => $pageId ],
			__METHOD__
		);

		return $this->convertRowsToUsages( $res );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function getEntityIdStrings( array $entityIds ) {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return EntityUsage[]
	 */
	private function convertRowsToUsages( Traversable $rows ) {
		$usages = [];

		foreach ( $rows as $object ) {
			try {
				$entityId = $this->idParser->parse( $object->eu_entity_id );
			} catch ( EntityIdParsingException $e ) {
				continue;
			}

			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $object->eu_aspect );

			$usage = new EntityUsage( $entityId, $aspect, $modifier );
			$key = $usage->getIdentityString();
			$usages[$key] = $usage;
		}

		return $usages;
	}

	/**
	 * Removes all usage tracking for a given page.
	 *
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	public function pruneUsages( $pageId ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}

		$old = $this->queryUsages( $pageId );

		$this->removeUsages( $pageId, $old );

		return $old;
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 */
	public function removeUsages( $pageId, array $usages ) {
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int.' );
		}
		if ( empty( $usages ) ) {
			return;
		}

		$rowIds = $this->getAffectedRowIds( $pageId, $usages );
		$rowIdChunks = array_chunk( $rowIds, $this->batchSize );

		$this->writeConnection->startAtomic( __METHOD__ );

		foreach ( $rowIdChunks as $chunk ) {
			$this->writeConnection->delete(
				$this->tableName,
				[
					'eu_row_id' => $chunk,
				],
				__METHOD__
			);
		}

		$this->writeConnection->endAtomic( __METHOD__ );
	}

	/**
	 * @see UsageLookup::getPagesUsing
	 *
	 * @param EntityId[] $entityIds
	 * @param string[] $aspects
	 *
	 * @return Traversable A traversable over PageEntityUsages grouped by page.
	 */
	public function getPagesUsing( array $entityIds, array $aspects = [] ) {
		if ( empty( $entityIds ) ) {
			return new ArrayIterator();
		}

		$idStrings = $this->getEntityIdStrings( $entityIds );
		$where = [ 'eu_entity_id' => $idStrings ];

		if ( !empty( $aspects ) ) {
			$where['eu_aspect'] = $aspects;
		}

		$res = $this->readConnection->select(
			$this->tableName,
			[ 'eu_page_id', 'eu_entity_id', 'eu_aspect' ],
			$where,
			__METHOD__,
			[ 'ORDER BY' => 'eu_page_id' ]
		);

		$pages = $this->foldRowsIntoPageEntityUsages( $res );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return PageEntityUsages[]
	 */
	private function foldRowsIntoPageEntityUsages( Traversable $rows ) {
		$usagesPerPage = [];

		foreach ( $rows as $row ) {
			$pageId = (int)$row->eu_page_id;

			if ( isset( $usagesPerPage[$pageId] ) ) {
				$pageEntityUsages = $usagesPerPage[$pageId];
			} else {
				$pageEntityUsages = new PageEntityUsages( $pageId );
			}

			$entityId = $this->idParser->parse( $row->eu_entity_id );
			list( $aspect, $modifier ) = EntityUsage::splitAspectKey( $row->eu_aspect );

			$usage = new EntityUsage( $entityId, $aspect, $modifier );
			$pageEntityUsages->addUsages( [ $usage ] );

			$usagesPerPage[$pageId] = $pageEntityUsages;
		}

		return $usagesPerPage;
	}

	/**
	 * @see UsageLookup::getUnusedEntities
	 *
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 */
	public function getUnusedEntities( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return [];
		}

		$entityIdMap = [];

		foreach ( $entityIds as $entityId ) {
			$idString = $entityId->getSerialization();
			$entityIdMap[$idString] = $entityId;
		}

		$usedIdStrings = $this->getUsedEntityIdStrings( array_keys( $entityIdMap ) );

		return array_diff_key( $entityIdMap, array_flip( $usedIdStrings ) );
	}

	/**
	 * Returns those entity ids which are used from a given set of entity ids.
	 *
	 * @param string[] $idStrings
	 *
	 * @return string[]
	 */
	private function getUsedEntityIdStrings( array $idStrings ) {
		// Note: We need to use one (sub)query per entity here, per T116404
		$subQueries = $this->getUsedEntityIdStringsQueries( $idStrings );

		if ( $this->readConnection->getType() === 'mysql' ) {
			 return $this->getUsedEntityIdStringsMySql( $subQueries );
		} else {
			$values = [];
			foreach ( $subQueries as $sql ) {
				$res = $this->readConnection->query( $sql, __METHOD__ );
				if ( $res->numRows() ) {
					$values[] = $res->current()->eu_entity_id;
				}
			}
		}

		return $values;
	}

	/**
	 * @param string[] $idStrings
	 *
	 * @return string[]
	 */
	private function getUsedEntityIdStringsQueries( array $idStrings ) {
		$subQueries = [];

		foreach ( $idStrings as $idString ) {
			$subQueries[] = $this->readConnection->selectSQLText(
				$this->tableName,
				'eu_entity_id',
				[ 'eu_entity_id' => $idString ],
				'',
				[ 'LIMIT' => 1 ]
			);
		}

		return $subQueries;
	}

	/**
	 * Returns the primary keys for the given where clause.
	 *
	 * @param array $where
	 * @param string $method Calling method
	 *
	 * @return int[]
	 */
	private function getPrimaryKeys( array $where, $method ) {
		$rowIds = $this->readConnection->selectFieldValues(
			$this->tableName,
			'eu_row_id',
			$where,
			$method
		);

		return array_map( 'intval', $rowIds ?: [] );
	}

	/**
	 * @param string[] $subQueries
	 * @return string[]
	 */
	private function getUsedEntityIdStringsMySql( array $subQueries ) {
		$values = [];

		// On MySQL we can UNION up queries and run them at once
		foreach ( array_chunk( $subQueries, $this->batchSize ) as $queryChunks ) {
			$sql = $this->readConnection->unionQueries( $queryChunks, true );

			$res = $this->readConnection->query( $sql, __METHOD__ );
			foreach ( $res as $row ) {
				$values[] = $row->eu_entity_id;
			}
		}

		return $values;
	}

}
