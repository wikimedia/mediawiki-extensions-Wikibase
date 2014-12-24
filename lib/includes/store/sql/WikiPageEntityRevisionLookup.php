<?php

namespace Wikibase\Lib\Store;

use DBAccessBase;
use DatabaseBase;
use DBQueryError;
use MWContentSerializationException;
use Revision;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityRevision;
use Wikibase\Lib\Store\EntityRedirect;

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server. This class also supports memcached (or accelerator) based caching
 * of entities.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityRevisionLookup extends DBAccessBase implements EntityRevisionLookup {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityIdParser $entityIdParser,
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		// TODO: migrate table away from using a numeric field so we no longer need this!
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @since 0.4
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER.
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = self::LATEST_FROM_SLAVE ) {
		wfProfileIn( __METHOD__ );
		$entityId = $this->getProperEntityId( $entityId );

		wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up entity ' . $entityId
			. " (revision $revisionId)." );

		// default changed from false to 0 and then to LATEST_FROM_SLAVE
		if ( $revisionId === false || $revisionId === 0 ) {
			wfWarn( 'getEntityRevision() called with $revisionId = false or 0, ' .
				'use EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER instead.' );
			$revisionId = self::LATEST_FROM_SLAVE;
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = null;

		if ( is_int( $revisionId ) ) {
			$row = $this->loadRevisionInformationById( $entityId, $revisionId );
		} else {
			$rows = $this->loadRevisionInformation( array( $entityId ), $revisionId );
			$row = $rows[$entityId->getSerialization()];
		}

		if ( $row ) {
			/** @var EntityRedirect $redirect */
			list( $entityRevision, $redirect ) = $this->loadEntity( $row );

			if ( $redirect !== null ) {
				// TODO: Optionally follow redirects. Doesn't make sense if a revision ID is given.
				wfProfileOut( __METHOD__ );
				throw new UnresolvedRedirectException( $redirect->getTargetId() );
			}

			if ( $entityRevision === null ) {
				// This only happens when there is a problem with the external store.
				wfLogWarning( __METHOD__ . ': Entity not loaded for ' . $entityId );
			}
		}

		if ( $entityRevision !== null && !$entityRevision->getEntity()->getId()->equals( $entityId ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Loaded wrong entity: Expected ' . $entityId
				. ', got ' . $entityRevision->getEntity()->getId() );

			throw new BadRevisionException( "Revision $revisionId does not belong to entity $entityId" );
		}

		if ( is_int( $revisionId ) && $entityRevision === null ) {
			// If a revision ID was specified, but that revision doesn't exist:
			throw new BadRevisionException( "No such revision found for $entityId: $revisionId" );
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * Returns an array like entityid -> EntityRevision, EntityRedirect or null (if not found)
	 * Please note that this doesn't throw UnresolvedRedirectExceptions but rather returns
	 * EntityRedirect objects for entities that are infact redirects.
	 *
	 * @since 0.5
	 * @see EntityRevisionLookup::getEntityRevisions
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would
	 *        force the revision to be determined from the canonical master database.
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return array entityid -> EntityRevision, EntityRedirect or null (if not found)
	 */
	public function getEntityRevisions( array $entityIds, $mode = self::LATEST_FROM_SLAVE ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$entityIds = $this->getProperEntityIds( $entityIds );

		$rows = $this->loadRevisionInformation( $entityIds, $mode );

		$entityRevisions = array();

		foreach ( $rows as $id => $row ) {
			if ( !$row ) {
				$entityRevisions[$id] = null;
				continue;
			}

			list( $entityRevision, $redirect ) = $this->loadEntity( $row );

			if ( $redirect !== null ) {
				$entityRevisions[$id] = $redirect;
			} elseif( $entityRevision ) {
				$entityRevisions[$id] = $entityRevision;
			} else {
				$entityRevisions[$id] = null;
			}
		}

		return $entityRevisions;
	}

	/**
	 * @see EntityRevisionLookup::getLatestRevisionId
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 * @param string $mode
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId, $mode = self::LATEST_FROM_SLAVE ) {
		$revisions = $this->getLatestRevisionIds( array( $entityId ), $mode );
		return $revisions[$entityId->getSerialization()];
	}

	/**
	 * @since 0.5
	 * @see EntityRevisionLookup::getLatestRevisionIds
	 *
	 * @param EntityId[] $entityIds
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would force the
	 *        revision to be determined from the canonical master database.
	 *
	 * @throws StorageException
	 * @return array entityid -> page_latest or false (if not found)
	 */
	public function getLatestRevisionIds( array $entityIds, $mode = self::LATEST_FROM_SLAVE ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$entityIds = $this->getProperEntityIds( $entityIds );

		$revisions = array();

		if ( $mode !== self::LATEST_FROM_MASTER ) {
			$revisions = $this->selectPageLatest( $entityIds, DB_SLAVE );
		}

		$loadFromMaster = array();
		foreach ( $entityIds as $entityId ) {
			if ( !isset( $revisions[$entityId->getSerialization()] ) || !is_int( $revisions[$entityId->getSerialization()] ) ) {
				$loadFromMaster[] = $entityId;
			}
		}

		if ( $loadFromMaster ) {
			$revisions = array_merge(
				$revisions,
				$this->selectPageLatest( $loadFromMaster, DB_MASTER )
			);
		}

		return $revisions;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode
	 *
	 * @throws DBQueryError
	 * @return array entityid -> object or false (if not found)
	 */
	private function loadRevisionInformation( array $entityIds, $mode ) {
		$rows = array();

		if ( $mode !== self::LATEST_FROM_MASTER ) {
			$rows = $this->selectRevisionInformation( $entityIds, DB_SLAVE );
		}

		$loadFromMaster = array();
		foreach ( $entityIds as $entityId ) {
			if ( !isset( $rows[$entityId->getSerialization()] ) || !$rows[$entityId->getSerialization()] ) {
				$loadFromMaster[] = $entityId;
			}
		}

		if ( $loadFromMaster ) {
			$rows = array_merge(
				$rows,
				$this->selectRevisionInformation( $loadFromMaster, DB_MASTER )
			);
		}

		return $rows;
	}

	/**
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @throws DBQueryError
	 * @return object|bool
	 */
	private function loadRevisionInformationById( EntityId $entityId, $revisionId ) {
		$row = $this->selectRevisionInformationById( $entityId, $revisionId, DB_SLAVE );

		if ( !$row ) {
			// Try loading from master
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load ' . $entityId
				. " with $revisionId from DB_MASTER." );

			$row = $this->selectRevisionInformationById( $entityId, $revisionId, DB_MASTER );
		}

		return $row;
	}

	/**
	 * Fields we need to select to load a revision
	 *
	 * @return string[]
	 */
	private function selectFields() {
		return array(
			'rev_id',
			'rev_content_format',
			'rev_timestamp',
			'old_id',
			'old_text',
			'old_flags'
		);
	}

	/**
	 * Selects revision information from the page, revision, and text tables.
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $revisionId The desired revision id
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return object|bool a raw database row object, or false if no such entity revision exists.
	 */
	private function selectRevisionInformationById( EntityId $entityId, $revisionId, $connType ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		// pick text via rev_text_id
		$join = array( 'text' => array( 'INNER JOIN', 'old_id=rev_text_id' ) );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revisionId of " . $entityId );

		$row = $db->selectRow(
			array( 'revision', 'text' ),
			$this->selectFields(),
			array( 'rev_id' => $revisionId ),
			__METHOD__,
			array(),
			$join
		);

		$this->releaseConnection( $db );

		wfProfileOut( __METHOD__ );
		return $row;
	}

	/**
	 * Selects revision information from the page, revision, and text tables.
	 * Returns an array like entityid -> object or false (if not found)
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return array
	 */
	private function selectRevisionInformation( array $entityIds, $connType ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		$join = array();
		$fields = $this->selectFields();
		// To be able to link rows with entity ids
		$fields[] = 'epp_entity_id';
		$fields[] = 'epp_entity_type';

		$tables = array( 'page', 'revision', 'text', 'wb_entity_per_page' );

		// pick page via epp_page_id
		$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );

		// pick latest revision via page_latest
		$join['revision'] = array( 'INNER JOIN', 'page_latest=rev_id' );

		// pick text via rev_text_id
		$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

		$res = $db->select( $tables, $fields, $this->getEppWhere( $entityIds, $db ), __METHOD__, array(), $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		$rows = array();
		// This sucks, but is the sanest option...
		foreach ( $res as $row ) {
			$rows[$row->epp_entity_type . $row->epp_entity_id] = $row;
		}

		$result = array();
		foreach ( $entityIds as $entityId ) {
			$result[$entityId->getSerialization()] = false;

			$key = $entityId->getEntityType() . $entityId->getNumericId();
			if ( isset( $rows[$key] ) ) {
				$result[$entityId->getSerialization()] = $rows[$key];
			}
		}

		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * Selects page_latest from the page table for the given entity ids.
	 * Returns an array like entityid -> page_latest or false (if not found)
	 *
	 * @since 0.4
	 *
	 * @param EntityId[] $entityIds
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return array
	 */
	private function selectPageLatest( array $entityIds, $connType = DB_SLAVE ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		$tables = array(
			'page',
			'wb_entity_per_page',
		);

		$join = array();

		// pick page via epp_page_id
		$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );
		$fields = array( 'page_latest', 'epp_entity_id', 'epp_entity_type' );

		$res = $db->select( $tables, $fields, $this->getEppWhere( $entityIds, $db ), __METHOD__, array(), $join );
		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			wfProfileOut( __METHOD__ );
			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		$rows = array();
		// This sucks, but is the sanest option...
		foreach ( $res as $row ) {
			$rows[$row->epp_entity_type . $row->epp_entity_id] = (int)$row->page_latest;
		}

		$pageLatest = array();
		foreach ( $entityIds as $entityId ) {
			$pageLatest[$entityId->getSerialization()] = false;

			$key = $entityId->getEntityType() . $entityId->getNumericId();
			if ( isset( $rows[$key] ) ) {
				$pageLatest[$entityId->getSerialization()] = $rows[$key];
			}
		}

		wfProfileOut( __METHOD__ );
		return $pageLatest;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param DatabaseBase $db
	 * @return string
	 */
	private function getEppWhere( array $entityIds, DatabaseBase $db ) {
		foreach ( $entityIds as &$entityId ) {
			$where[] = $db->makeList( array(
				'epp_entity_id' => $entityId->getNumericId(),
				'epp_entity_type' => $entityId->getEntityType()
			), LIST_AND );
		}

		return $db->makeList( $where, LIST_OR );
	}

	/**
	 * @todo: migrate table away from using a numeric field & get rid of this function
	 *
	 * @param EntityId $id
	 *
	 * @return mixed
	 */
	private function getProperEntityId( EntityId $id ) {
		return $this->entityIdParser->parse( $id->getSerialization() );
	}

	/**
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 */
	private function getProperEntityIds( array $entityIds ) {
		foreach ( $entityIds as &$entityId ) {
			$entityId = $this->getProperEntityId( $entityId );
		}

		return $entityIds;
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * @see loadEntityBlob()
	 *
	 * @param object $row a row object as expected Revision::getRevisionText(). That is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 * @return object[] list( EntityRevision|null $entityRevision, EntityRedirect|null $entityRedirect )
	 * with either $entityRevision or $entityRedirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $row ) {
		wfProfileIn( __METHOD__ );

		$blob = $this->loadEntityBlob( $row );
		$entity = $this->contentCodec->decodeEntity( $blob, $row->rev_content_format );

		if ( $entity ) {
			$entityRevision = new EntityRevision( $entity, (int)$row->rev_id, $row->rev_timestamp );

			$result = array( $entityRevision, null );
		} else {
			$redirect = $this->contentCodec->decodeRedirect( $blob, $row->rev_content_format );

			if ( !$redirect ) {
				throw new MWContentSerializationException(
					'The serialized data contains neither an Entity nor an EntityRedirect!'
				);
			}

			$result = array( null, $redirect );
		}

		wfProfileOut( __METHOD__ );
		return $result;
	}

	/**
	 * Loads a blob based on a database row from the revision and text tables.
	 *
	 * This calls Revision::getRevisionText to resolve any additional indirections in getting
	 * to the actual blob data, like the "External Store" mechanism used by Wikipedia & co.
	 *
	 * @param object $row a row object as expected Revision::getRevisionText(). That is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 *
	 * @return string The blob
	 */
	private function loadEntityBlob( $row ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Calling getRevisionText() on revision '
			. $row->rev_id );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = Revision::getRevisionText( $row, 'old_', $this->wiki );

		wfProfileOut( __METHOD__ );

		if ( $blob === false ) {
			wfWarn( 'Unable to load raw content blob for revision ' . $row->rev_id );

			throw new MWContentSerializationException(
				'Unable to load raw content blob for revision ' . $row->rev_id
			);
		}

		return $blob;
	}

}
