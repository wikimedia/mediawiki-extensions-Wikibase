<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Usage\Sql;

use ArrayIterator;
use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use MWException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Traversable;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\PageEntityUsages;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\Lib\Rdbms\ClientDomainDb;
use Wikimedia\Rdbms\DBUnexpectedError;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\Platform\ISQLPlatform;

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

	public const DEFAULT_TABLE_NAME = 'wbc_entity_usage';

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var IDatabase
	 */
	private $writeConnection;

	/**
	 * @var ClientDomainDb
	 */
	private $db;

	/**
	 * @var int
	 */
	private $batchSize;

	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var int
	 */
	private $addUsagesBatchSize;

	/** @var LoggerInterface */
	private $logger;

	/**
	 * @param EntityIdParser $idParser
	 * @param IDatabase $writeConnection
	 * @param int $batchSize Batch size for database queries on the entity usage table, including
	 *  INSERTs, SELECTs, and DELETEs. Defaults to 100.
	 * @param string|null $tableName defaults to wbc_entity_usage
	 * @param int $addUsagesBatchSize Batch size for adding entity usage records. Can also be set after construction.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(
		EntityIdParser $idParser,
		IDatabase $writeConnection,
		int $batchSize = 100,
		?string $tableName = null,
		int $addUsagesBatchSize = 500
	) {
		if ( $batchSize < 1 ) {
			throw new InvalidArgumentException( '$batchSize must be an integer >= 1' );
		}

		if ( $addUsagesBatchSize < 1 ) {
			throw new InvalidArgumentException( '$addUsagesBatchSize must be an integer >= 1' );
		}

		$this->idParser = $idParser;
		// Several places inject read connection instead. Fix those.
		$this->writeConnection = $writeConnection;
		$this->batchSize = $batchSize;
		$this->tableName = $tableName ?: self::DEFAULT_TABLE_NAME;
		$this->addUsagesBatchSize = $addUsagesBatchSize;

		//TODO: Inject
		$this->db = WikibaseClient::getClientDomainDbFactory()->newLocalDb();
		$this->logger = LoggerFactory::getInstance( 'Wikibase' );
	}

	/**
	 * @param int $pageId
	 * @param EntityUsage[] $usages
	 *
	 * @return int[] affected row ids
	 * @throws DBUnexpectedError
	 * @throws MWException
	 */
	private function getAffectedRowIds( int $pageId, array $usages ): array {
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
				'eu_page_id' => $pageId,
				$db->makeList( $usageConditionChunk, LIST_OR ),
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
	private function makeUsageRows( int $pageId, array $usages ): array {
		$rows = [];

		if ( $pageId < 1 ) {
			$this->logger->warning( __METHOD__ . ': skipping invalid page ID {pageId} (T264929)', [
				'pageId' => $pageId,
				'exception' => new RuntimeException(),
			] );
			return [];
		}

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$rows[] = [
				'eu_page_id' => $pageId,
				'eu_aspect' => $usage->getAspectKey(),
				'eu_entity_id' => $usage->getEntityId()->getSerialization(),
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
	public function addUsages( int $pageId, array $usages ): int {
		if ( empty( $usages ) ) {
			return 0;
		}

		$batches = array_chunk(
			$this->makeUsageRows( $pageId, $usages ),
			$this->addUsagesBatchSize
		);

		$c = 0;

		foreach ( $batches as $rows ) {
			$this->writeConnection->insert( $this->tableName, $rows, __METHOD__, [ 'IGNORE' ] );
			$c += $this->writeConnection->affectedRows();

			// Wait for all database replicas to be updated, but only for the affected client wiki.
			$this->db->replication()->wait();
		}

		return $c;
	}

	/**
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[] EntityUsage identity string => EntityUsage
	 */
	public function queryUsages( int $pageId ): array {
		$res = $this->db->connections()->getReadConnection()->newSelectQueryBuilder()
			->select( [ 'eu_aspect', 'eu_entity_id' ] )
			->from( $this->tableName )
			->where( [ 'eu_page_id' => $pageId ] )
			->caller( __METHOD__ )->fetchResultSet();

		return $this->convertRowsToUsages( $res );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return string[]
	 */
	private function getEntityIdStrings( array $entityIds ): array {
		return array_map( function( EntityId $id ) {
			return $id->getSerialization();
		}, $entityIds );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return EntityUsage[]
	 */
	private function convertRowsToUsages( Traversable $rows ): array {
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
	public function pruneUsages( int $pageId ): array {
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
	public function removeUsages( int $pageId, array $usages ): void {
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

		$queryBuilder = $this->db->connections()->getReadConnection()->newSelectQueryBuilder()
			->select( [ 'eu_page_id', 'eu_entity_id', 'eu_aspect' ] )
			->from( $this->tableName )
			->where( [
				'eu_entity_id' => $this->getEntityIdStrings( $entityIds ),
			] );
		if ( !empty( $aspects ) ) {
			$queryBuilder->andWhere( [ 'eu_aspect' => $aspects ] );
		}
		$res = $queryBuilder
			->orderBy( 'eu_page_id' )
			->caller( __METHOD__ )->fetchResultSet();

		$pages = $this->foldRowsIntoPageEntityUsages( $res );

		//TODO: use paging for large page sets!
		return new ArrayIterator( $pages );
	}

	/**
	 * @param Traversable $rows
	 *
	 * @return PageEntityUsages[]
	 */
	private function foldRowsIntoPageEntityUsages( Traversable $rows ): array {
		$usagesPerPage = [];

		foreach ( $rows as $row ) {
			$pageId = (int)$row->eu_page_id;

			if ( $pageId < 1 ) {
				$this->logger->warning( __METHOD__ . ': skipping invalid page ID {pageId} (T264929)', [
					'pageId' => $pageId,
					'row' => $row,
					'exception' => new RuntimeException(),
				] );
				continue;
			}

			$pageEntityUsages = $usagesPerPage[$pageId] ?? new PageEntityUsages( $pageId );

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
	public function getUnusedEntities( array $entityIds ): array {
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
	private function getUsedEntityIdStrings( array $idStrings ): array {
		// Note: We need to use one (sub)query per entity here, per T116404
		$subQueries = $this->getUsedEntityIdStringsQueries( $idStrings );
		$readConnection = $this->db->connections()->getReadConnection();

		if ( $readConnection->getType() === 'mysql' ) {
			return $this->getUsedEntityIdStringsMySql( $subQueries, $readConnection );
		} else {
			$values = [];
			foreach ( $subQueries as $sql ) {
				$res = $readConnection->query( $sql, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );
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
	private function getUsedEntityIdStringsQueries( array $idStrings ): array {
		$subQueries = [];
		$readConnection = $this->db->connections()->getReadConnection();

		foreach ( $idStrings as $idString ) {
			$subQueries[] = $readConnection->newSelectQueryBuilder()
				->select( 'eu_entity_id' )
				->from( $this->tableName )
				->where( [ 'eu_entity_id' => $idString ] )
				->limit( 1 )
				->getSQL();
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
	private function getPrimaryKeys( array $where, string $method ): array {
		$rowIds = $this->db->connections()->getReadConnection()->newSelectQueryBuilder()
			->select( [ 'eu_row_id' ] )
			->from( $this->tableName )
			->where( $where )
			->caller( $method )->fetchFieldValues();

		return array_map( 'intval', $rowIds ?: [] );
	}

	/**
	 * @param string[] $subQueries
	 * @param IDatabase $readConnection must have type MySQL
	 * @return string[]
	 */
	private function getUsedEntityIdStringsMySql(
		array $subQueries,
		IDatabase $readConnection
	): array {
		$values = [];

		// On MySQL we can UNION up queries and run them at once
		foreach ( array_chunk( $subQueries, $this->batchSize ) as $queryChunks ) {
			$sql = $readConnection->unionQueries( $queryChunks, true );
			$res = $readConnection->query( $sql, __METHOD__, ISQLPlatform::QUERY_CHANGE_NONE );
			foreach ( $res as $row ) {
				$values[] = $row->eu_entity_id;
			}
		}

		return $values;
	}

	/**
	 * Set the batch size for adding entity usage records.
	 * This can also be set in the constructor.
	 * @param int $addUsagesBatchSize
	 */
	public function setAddUsagesBatchSize( int $addUsagesBatchSize ): void {
		if ( $addUsagesBatchSize < 1 ) {
			throw new InvalidArgumentException( '$addUsagesBatchSize must be an integer >= 1' );
		}

		$this->addUsagesBatchSize = $addUsagesBatchSize;
	}

}
