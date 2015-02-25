<?php

namespace Wikibase\Lib\Store;

use DBAccessBase;
use DBQueryError;
use MWContentSerializationException;
use Revision;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityRevision;

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server. This class also supports memcached (or accelerator) based caching
 * of entities.
 *
 * @since 0.3
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
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

		$row = $this->loadRevisionRow( $entityId, $revisionId );

		if ( $row ) {
			/** @var EntityRedirect $redirect */
			list( $entityRevision, $redirect ) = $this->loadEntity( $row );

			if ( $redirect !== null ) {
				// TODO: Optionally follow redirects. Doesn't make sense if a revision ID is given.
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

		return $entityRevision;
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
		$row = null;

		if ( $mode !== self::LATEST_FROM_MASTER ) {
			$row = $this->selectPageLatest( $entityId, DB_SLAVE );
		}

		if ( !$row ) {
			$row = $this->selectPageLatest( $entityId, DB_MASTER );
		}

		if ( $row ) {
			return (int)$row->page_latest;
		}

		return false;
	}

	/**
	 * @param EntityId $entityId
	 * @param int|string $revisionId
	 *
	 * @throws DBQueryError
	 * @return object|null
	 */
	private function loadRevisionRow( EntityId $entityId, $revisionId ) {
		$row = null;

		if ( $revisionId !== self::LATEST_FROM_MASTER ) {
			$row = $this->selectRevisionRow( $entityId, $revisionId, DB_SLAVE );
		}

		if ( !$row ) {
			// try loading from master
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load ' . $entityId
				. " with $revisionId from DB_MASTER." );

			$row = $this->selectRevisionRow( $entityId, $revisionId, DB_MASTER );
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
	 * @param int|string $revisionId The desired revision id, or LATEST_FROM_SLAVE or LATEST_FROM_MASTER.
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return object|null a raw database row object, or null if no such entity revision exists.
	 */
	private function selectRevisionRow( EntityId $entityId, $revisionId, $connType ) {
		$db = $this->getConnection( $connType );

		$where = array();
		$join = array();

		if ( is_int( $revisionId ) ) {
			$tables = array( 'revision', 'text' );

			// pick revision by id
			$where['rev_id'] = $revisionId;

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revisionId of " . $entityId );
		} else {
			$tables = array( 'page', 'revision', 'text', 'wb_entity_per_page' );

			$entityId = $this->getProperEntityId( $entityId );

			// pick entity by id
			$where['epp_entity_id'] = $entityId->getNumericId();
			$where['epp_entity_type'] = $entityId->getEntityType();

			// pick page via epp_page_id
			$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );

			// pick latest revision via page_latest
			$join['revision'] = array( 'INNER JOIN', 'page_latest=rev_id' );

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up latest revision of ' . $entityId );
		}

		$res = $db->select( $tables, $this->selectFields(), $where, __METHOD__, array(), $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		if ( !( $row = $res->fetchObject() ) ) {
			$row = null;
		}

		return $row;
	}

	/**
	 * Selects page information from the page table.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return object|null a raw database row object, or null if no such entity revision exists.
	 */
	protected function selectPageLatest( EntityId $entityId, $connType = DB_SLAVE ) {
		$db = $this->getConnection( $connType );

		$tables = array(
			'page',
			'wb_entity_per_page',
		);

		$where = array();
		$join = array();

		$entityId = $this->getProperEntityId( $entityId );

		// pick entity by id
		$where['epp_entity_id'] = $entityId->getNumericId();
		$where['epp_entity_type'] = $entityId->getEntityType();

		// pick page via epp_page_id
		$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );

		$res = $db->select( $tables, 'page_latest', $where, __METHOD__, array(), $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();
			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		if ( !( $row = $res->fetchObject() ) ) {
			$row = null;
		}

		return $row;
	}

	/**
	 * @todo: migrate table away from using a numeric field & get rid of this function
	 *
	 * @param EntityId $id
	 *
	 * @return mixed
	 */
	protected function getProperEntityId( EntityId $id ) {
		return $this->entityIdParser->parse( $id->getSerialization() );
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
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Calling getRevisionText() on revision '
			. $row->rev_id );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = Revision::getRevisionText( $row, 'old_', $this->wiki );

		if ( $blob === false ) {
			wfWarn( 'Unable to load raw content blob for revision ' . $row->rev_id );

			throw new MWContentSerializationException(
				'Unable to load raw content blob for revision ' . $row->rev_id
			);
		}

		return $blob;
	}

}
