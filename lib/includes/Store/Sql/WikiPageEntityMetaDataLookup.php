<?php

namespace Wikibase\Lib\Store\Sql;

use DBAccessBase;
use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use stdClass;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikimedia\Rdbms\DBQueryError;

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
class WikiPageEntityMetaDataLookup extends DBAccessBase implements WikiPageEntityMetaDataAccessor {

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
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var EntitySource
	 */
	private $entitySource;

	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;

	/**
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param PageTableEntityQuery $pageTableEntityConditionGenerator
	 * @param EntitySource $entitySource
	 * @param DataAccessSettings $dataAccessSettings
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 * @param string $repositoryName The name of the repository to lookup from (use an empty string for the local repository)
	 */
	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		PageTableEntityQuery $pageTableEntityConditionGenerator,
		EntitySource $entitySource,
		DataAccessSettings $dataAccessSettings,
		$wiki = false,
		$repositoryName = ''
	) {
		RepositoryNameAssert::assertParameterIsValidRepositoryName( $repositoryName, '$repositoryName' );

		$databaseName = $dataAccessSettings->useEntitySourceBasedFederation() ? $entitySource->getDatabaseName() : $wiki;

		parent::__construct( $databaseName );
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->pageTableEntityQuery = $pageTableEntityConditionGenerator;
		$this->entitySource = $entitySource;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->repositoryName = $repositoryName;

		// TODO: Inject
		$this->logger = LoggerFactory::getInstance( 'Wikibase' );
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @throws InvalidArgumentException When some of $entityIds does not belong the repository of this lookup
	 *
	 * @return (stdClass|bool)[] Array mapping entity ID serializations to either objects or false if an entity
	 *  could not be found.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		$rows = [];

		$this->assertCanHandleEntityIds( $entityIds );

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_MASTER ) {
			$rows = $this->selectRevisionInformationMultiple( $entityIds, DB_REPLICA );
		}

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_REPLICA ) {
			// Attempt to load (missing) rows from master if the caller asked for that.
			$loadFromMaster = [];
			/** @var EntityId $entityId */
			foreach ( $entityIds as $entityId ) {
				if ( !isset( $rows[$entityId->getSerialization()] ) || !$rows[$entityId->getSerialization()] ) {
					$loadFromMaster[] = $entityId;
				}
			}

			if ( $loadFromMaster ) {
				$rows = array_merge(
					$rows,
					$this->selectRevisionInformationMultiple( $loadFromMaster, DB_MASTER )
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
		$mode = EntityRevisionLookup::LATEST_FROM_MASTER
	) {
		$this->assertCanHandleEntityId( $entityId );

		$row = $this->selectRevisionInformationById( $entityId, $revisionId, DB_REPLICA );

		if ( !$row && $mode !== EntityRevisionLookup::LATEST_FROM_REPLICA ) {
			// Try loading from master, unless the caller only wants replica data.
			$this->logger->debug(
				'{method}: try to load {entityId} with {revisionId} from DB_MASTER.',
				[
					'method' => __METHOD__,
					'entityId' => $entityId,
					'revisionId' => $revisionId,
				]
			);

			$row = $this->selectRevisionInformationById( $entityId, $revisionId, DB_MASTER );
		}

		return $row;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_REPLICA,
	 *     EntityRevisionLookup::LATEST_FROM_REPLICA_WITH_FALLBACK or
	 *     EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @throws InvalidArgumentException When some of $entityIds does not belong the repository of this lookup
	 *
	 * @return (int|bool)[] Array mapping entity ID serializations to revision IDs
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	public function loadLatestRevisionIds( array $entityIds, $mode ) {
		$revisionIds = [];

		$this->assertCanHandleEntityIds( $entityIds );

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_MASTER ) {
			$revisionIds = $this->selectLatestRevisionIdsMultiple( $entityIds, DB_REPLICA );
		}

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_REPLICA ) {
			// Attempt to load (missing) rows from master if the caller asked for that.
			$loadFromMaster = [];
			/** @var EntityId $entityId */
			foreach ( $entityIds as $entityId ) {
				if ( !isset( $revisionIds[$entityId->getSerialization()] ) || !$revisionIds[$entityId->getSerialization()] ) {
					$loadFromMaster[] = $entityId;
				}
			}

			if ( $loadFromMaster ) {
				$revisionIds = array_merge(
					$revisionIds,
					$this->selectLatestRevisionIdsMultiple( $loadFromMaster, DB_MASTER )
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
	private function assertCanHandleEntityIds( array $entityIds ) {
		foreach ( $entityIds as $entityId ) {
			$this->assertCanHandleEntityId( $entityId );
		}
	}

	private function assertCanHandleEntityId( EntityId $entityId ) {
		if ( $this->dataAccessSettings->useEntitySourceBasedFederation() ) {
			$this->assertEntityIdFromKnownSource( $entityId );
			return;
		}

		$this->assertEntityIdFromRightRepository( $entityId );
	}

	private function assertEntityIdFromKnownSource( EntityId $entityId ) {
		if ( !in_array( $entityId->getEntityType(), $this->entitySource->getEntityTypes() ) ) {
			throw new InvalidArgumentException(
				'Could not load data from the database of entity source: ' .
				$this->entitySource->getSourceName()
			);
		}
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return bool
	 */
	private function isEntityIdFromRightRepository( EntityId $entityId ) {
		return $entityId->getRepositoryName() === $this->repositoryName;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws InvalidArgumentException When $entityId does not belong the repository of this lookup
	 */
	private function assertEntityIdFromRightRepository( EntityId $entityId ) {
		if ( !$this->isEntityIdFromRightRepository( $entityId ) ) {
			throw new InvalidArgumentException(
				'Could not load data from the database of repository: ' .
				$entityId->getRepositoryName()
			);
		}
	}

	/**
	 * Fields we need to select to load a revision
	 *
	 * @return string[]
	 */
	private function selectFields() {
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
	 * @param int $connType DB_REPLICA or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return stdClass|bool a raw database row object, or false if no such entity revision exists.
	 */
	private function selectRevisionInformationById( EntityId $entityId, $revisionId, $connType ) {
		$db = $this->getConnection( $connType );

		$join = [];
		$join['page'] = [ 'INNER JOIN', 'rev_page=page_id' ];

		$this->logger->debug(
			'{method}: Looking up revision {revisionId} of {entityId}.',
			[
				'method' => __METHOD__,
				'revisionId' => $revisionId,
				'entityId' => $entityId,
			]
		);

		$fields = $this->selectFields();

		// Attach the appropriate role name.
		// This could as well come from the database, if the query was written accordingly.
		$roleName = $this->entityNamespaceLookup->getEntitySlotRole(
			$entityId->getEntityType()
		);
		$fields['role_name'] = $db->addQuotes( $roleName );

		$row = $db->selectRow(
			[ 'revision', 'page' ],
			$fields,
			[ 'rev_id' => $revisionId ],
			__METHOD__,
			[],
			$join
		);

		$this->releaseConnection( $db );

		return $row;
	}

	/**
	 * Selects revision information from the page and revision tables.
	 * Returns an array like entityid -> object or false (if not found).
	 *
	 * @param EntityId[] $entityIds The entities to query the DB for.
	 * @param int $connType DB_REPLICA or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return (stdClass|false)[] Array mapping entity ID serializations to either objects or false if an entity
	 *  could not be found.
	 */
	private function selectRevisionInformationMultiple( array $entityIds, $connType ) {
		$db = $this->getConnection( $connType );

		$rows = $this->pageTableEntityQuery->selectRows(
			$this->selectFields(),
			[ 'revision' => [ 'INNER JOIN', 'page_latest=rev_id' ] ],
			$entityIds,
			$db
		);

		$this->releaseConnection( $db );

		return $this->processRows( $entityIds, $rows );
	}

	/**
	 * Selects page_latest information from the page table.
	 * Returns an array like entityid -> int or false (if not found).
	 *
	 * @param EntityId[] $entityIds The entities to query the DB for.
	 * @param int $connType DB_REPLICA or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return array Array mapping entity ID serializations to either ints
	 * or false if an entity could not be found (including if the page is a redirect).
	 */
	private function selectLatestRevisionIdsMultiple( array $entityIds, $connType ) {
		$db = $this->getConnection( $connType );

		$rows = $this->pageTableEntityQuery->selectRows(
			[ 'page_title', 'page_latest', 'page_is_redirect' ],
			[],
			$entityIds,
			$db
		);

		$this->releaseConnection( $db );

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
	private function processRows( array $entityIds, array $rows ) {
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
