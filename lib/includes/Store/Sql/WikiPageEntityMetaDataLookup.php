<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Store\Sql;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use stdClass;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\LookupConstants;
use Wikimedia\Rdbms\DBQueryError;
use Wikimedia\Rdbms\IDatabase;

/**
 * Service for looking up meta data about one or more entities as needed for
 * loading entities from WikiPages (via Revision) or to verify an entity against
 * page.page_latest.
 *
 * This lookup makes the assumption that the page title storing the entity matches the local ID
 * part of the entity ID as this class queries against the page_title field of the page table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityMetaDataLookup implements WikiPageEntityMetaDataAccessor {

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var PageTableEntityQuery
	 */
	private $pageTableEntityQuery;

	/**
	 * @var LoggerInterface
	 */
	private $logger;

	/**
	 * @var DatabaseEntitySource
	 */
	private $entitySource;

	/**
	 * @var RepoDomainDb
	 */
	private $repoDb;

	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		PageTableEntityQuery $pageTableEntityConditionGenerator,
		DatabaseEntitySource $entitySource,
		RepoDomainDb $repoDb,
		LoggerInterface $logger = null
	) {
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->pageTableEntityQuery = $pageTableEntityConditionGenerator;
		$this->entitySource = $entitySource;
		$this->repoDb = $repoDb;
		$this->logger = $logger ?: new NullLogger();
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @throws InvalidArgumentException When some of $entityIds does not belong the repository of this lookup
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects or false if an entity
	 *  could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ): array {
		$rows = [];

		$this->assertCanHandleEntityIds( $entityIds );

		if ( $mode !== LookupConstants::LATEST_FROM_MASTER ) {
			$dbReplicaRead = $this->repoDb->connections()->getReadConnection();
			$rows = $this->selectRevisionInformationMultiple( $entityIds, $dbReplicaRead );
		}

		if ( $mode !== LookupConstants::LATEST_FROM_REPLICA ) {
			// Attempt to load (missing) rows from master if the caller asked for that.
			$loadFromMaster = [];
			/** @var EntityId $entityId */
			foreach ( $entityIds as $entityId ) {
				if ( !isset( $rows[$entityId->getSerialization()] ) || !$rows[$entityId->getSerialization()] ) {
					$loadFromMaster[] = $entityId;
				}
			}

			if ( $loadFromMaster ) {
				$dbPrimaryRead = $this->repoDb->connections()->getWriteConnection();
				$rows = array_merge(
					$rows,
					$this->selectRevisionInformationMultiple( $loadFromMaster, $dbPrimaryRead )
				);
			}
		}

		return $rows;
	}

	/**
	 * @param EntityId $entityId
	 * @param int $revisionId
	 * @param string $mode (WikiPageEntityMetaDataAccessor::LATEST_FROM_REPLICA,
	 *     WikiPageEntityMetaDataAccessor::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     WikiPageEntityMetaDataAccessor::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @throws InvalidArgumentException When $entityId does not belong the repository of this lookup
	 *
	 * @return stdClass|bool
	 */
	public function loadRevisionInformationByRevisionId(
		EntityId $entityId,
		$revisionId,
		$mode = LookupConstants::LATEST_FROM_MASTER
	) {
		$this->assertCanHandleEntityId( $entityId );

		$dbReplicaRead = $this->repoDb->connections()->getReadConnection();
		$row = $this->selectRevisionInformationById( $entityId, $revisionId, $dbReplicaRead );

		if ( !$row && $mode !== LookupConstants::LATEST_FROM_REPLICA ) {
			// Try loading from master, unless the caller only wants replica data.
			$this->logger->debug(
				'{method}: try to load {entityId} with {revisionId} from DB_PRIMARY.',
				[
					'method' => __METHOD__,
					'entityId' => $entityId,
					'revisionId' => $revisionId,
				]
			);

			$dbPrimaryRead = $this->repoDb->connections()->getWriteConnection();
			$row = $this->selectRevisionInformationById( $entityId, $revisionId, $dbPrimaryRead );
		}

		return $row;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode ( LookupConstants::LATEST_FROM_REPLICA,
	 *     LookupConstants::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     LookupConstants::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @throws InvalidArgumentException When some of $entityIds does not belong the repository of this lookup
	 *
	 * @return (int|bool)[] Array mapping entity ID serializations to revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ): array {
		$revisionIds = [];

		$this->assertCanHandleEntityIds( $entityIds );

		if ( $mode !== LookupConstants::LATEST_FROM_MASTER ) {
			$dbReplicaRead = $this->repoDb->connections()->getReadConnection();
			$revisionIds = $this->selectLatestRevisionIdsMultiple( $entityIds, $dbReplicaRead );
		}

		if ( $mode !== LookupConstants::LATEST_FROM_REPLICA ) {
			// Attempt to load (missing) rows from master if the caller asked for that.
			$loadFromMaster = [];
			/** @var EntityId $entityId */
			foreach ( $entityIds as $entityId ) {
				if ( !isset( $revisionIds[$entityId->getSerialization()] ) || !$revisionIds[$entityId->getSerialization()] ) {
					$loadFromMaster[] = $entityId;
				}
			}

			if ( $loadFromMaster ) {
				$dbPrimaryRead = $this->repoDb->connections()->getWriteConnection();
				$revisionIds = array_merge(
					$revisionIds,
					$this->selectLatestRevisionIdsMultiple( $loadFromMaster, $dbPrimaryRead )
				);
			}
		}

		return $revisionIds;
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @throws InvalidArgumentException When some of $entityIds cannot be handled by this lookup
	 */
	private function assertCanHandleEntityIds( array $entityIds ): void {
		foreach ( $entityIds as $entityId ) {
			$this->assertCanHandleEntityId( $entityId );
		}
	}

	private function assertCanHandleEntityId( EntityId $entityId ): void {
		if ( !in_array( $entityId->getEntityType(), $this->entitySource->getEntityTypes() ) ) {
			throw new InvalidArgumentException(
				'Could not load data from the database of entity source: ' .
				$this->entitySource->getSourceName()
			);
		}
	}

	/**
	 * Fields we need to select to load a revision
	 *
	 * @return string[]
	 */
	private function selectFields(): array {
		 // XXX: This could just call RevisionStore::getQueryInfo and
		//  use the list of fields from there.
		return [
			'rev_id',
			'rev_timestamp',
			'page_latest',
			'page_is_redirect',
		];
	}

	/**
	 * Selects revision information from the page and revision tables.
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $revisionId The desired revision id
	 * @param IDatabase $db A connection to either DB_REPLICA or DB_PRIMARY
	 *
	 * @throws DBQueryError If the query fails.
	 * @return stdClass|bool a raw database row object, or false if no such entity revision exists.
	 */
	private function selectRevisionInformationById( EntityId $entityId, int $revisionId, IDatabase $db ) {
		$rows = $this->pageTableEntityQuery->selectRows(
			$this->selectFields(),
			[ 'revision' => [ 'INNER JOIN', [ 'rev_page=page_id', 'rev_id' => $revisionId ] ] ],
			[ $entityId ],
			$db
		);

		return $this->processRows( [ $entityId ], $rows )[$entityId->getSerialization()];
	}

	/**
	 * Selects revision information from the page and revision tables.
	 * Returns an array like entityid -> object or false (if not found).
	 *
	 * @param EntityId[] $entityIds The entities to query the DB for.
	 * @param IDatabase $db connection to DB_REPLICA or DB_PRIMARY database to query
	 *
	 * @throws DBQueryError If the query fails.
	 * @return (stdClass|false)[] Array mapping entity ID serializations to either objects or false if an entity
	 *  could not be found.
	 */
	private function selectRevisionInformationMultiple( array $entityIds, IDatabase $db ): array {
		$rows = $this->pageTableEntityQuery->selectRows(
			$this->selectFields(),
			[ 'revision' => [ 'INNER JOIN', 'page_latest=rev_id' ] ],
			$entityIds,
			$db
		);

		return $this->processRows( $entityIds, $rows );
	}

	/**
	 * Selects page_latest information from the page table.
	 * Returns an array like entityid -> int or false (if not found).
	 *
	 * @param EntityId[] $entityIds The entities to query the DB for.
	 * @param IDatabase $db connection to the DB_REPLICA or DB_PRIMARY database to query from
	 *
	 * @throws DBQueryError If the query fails.
	 * @return array Array mapping entity ID serializations to either ints
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	private function selectLatestRevisionIdsMultiple( array $entityIds, IDatabase $db ): array {
		$rows = $this->pageTableEntityQuery->selectRows(
			[ 'page_title', 'page_latest', 'page_is_redirect' ],
			[],
			$entityIds,
			$db
		);

		return array_map(
			function ( $revisionInformation ) {
				if ( !is_object( $revisionInformation ) ) {
					return $revisionInformation;
				}

				if ( $revisionInformation->page_is_redirect ) {
					return false;
				}

				return $revisionInformation->page_latest;
			},

			$this->processRows( $entityIds, $rows )
		);
	}

	/**
	 * Takes an array of rows and returns a result where every given entity ID has some value.
	 *
	 * @param EntityId[] $entityIds
	 * @param stdClass[] $rows indexed by entity id serialization
	 *
	 * @return (stdClass|false)[] Array mapping entity ID serializations to either objects or false if an entity
	 *  is not present in $res.
	 */
	private function processRows( array $entityIds, array $rows ): array {
		$result = [];
		foreach ( $entityIds as $entityId ) {
			// $rows is indexed by page titles without repository prefix but we want to keep prefixes
			// in the results returned by the lookup to match the input $entityIds
			$serializedId = $entityId->getSerialization();
			$idLocalPart = $entityId->getLocalPart();

			$result[$serializedId] = false;

			if ( isset( $rows[$idLocalPart] ) ) {
				$row = $rows[$idLocalPart];

				// Attach the appropriate role name.
				// This could as well come from the database, if the query was written accordingly.
				$row->role_name = $this->entityNamespaceLookup->getEntitySlotRole(
					$entityId->getEntityType()
				);

				$result[$serializedId] = $row;
			}
		}

		return $result;
	}

}
