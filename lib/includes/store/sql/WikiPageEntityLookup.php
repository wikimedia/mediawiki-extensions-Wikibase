<?php

namespace Wikibase\Lib\Store;

use DBAccessBase;
use DBQueryError;
use MWContentSerializationException;
use Revision;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityFactory;
use Wikibase\EntityRevision;
use Wikibase\StorageException;
use Wikibase\UnresolvedRedirectException;

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
class WikiPageEntityLookup extends DBAccessBase implements EntityRevisionLookup {

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var EntityFactory
	 */
	private $entityFactory;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityFactory $entityFactory
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityFactory $entityFactory,
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;
		$this->entityFactory = $entityFactory;

		// TODO: migrate table away from using a numeric field so we no longer need this!
		$this->idParser = new BasicEntityIdParser();
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revisionId = 0 ) {
		$entityRevision = $this->getEntityRevision( $entityId, $revisionId );
		return $entityRevision === null ? null : $entityRevision->getEntity();
	}

	/**
	 * @since 0.4
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revisionId The desired revision id, 0 means "current".
	 *
	 * @throws StorageException
	 * @return EntityRevision|null
	 */
	public function getEntityRevision( EntityId $entityId, $revisionId = 0 ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Looking up entity ' . $entityId
			. " (revision $revisionId)." );

		if ( $revisionId === false ) { // default changed from false to 0
			wfWarn( 'getEntityRevision() called with $revisionId = false, use 0 instead.' );
			$revisionId = 0;
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = null;

		$row = $this->loadRevisionRow( $entityId, $revisionId );

		if ( $row ) {
			list( $entityRevision, $redirect ) = $this->loadEntity( $row );

			if ( $redirect !== null ) {
				// TODO: Optionally follow redirects. Doesn't make sense if a revision ID is given.
				wfProfileOut( __METHOD__ );
				throw new UnresolvedRedirectException( $redirect );
			}

			if ( $entityRevision === null ) {
				// This only happens when there is a problem with the external store.
				wfLogWarning( __METHOD__ . ': Entity not loaded for ' . $entityId );
			}
		}

		if ( $entityRevision !== null && !$entityRevision->getEntity()->getId()->equals( $entityId ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Loaded wrong entity: Expected ' . $entityId
				. ', got ' . $entityRevision->getEntity()->getId() . '.' );

			$entityRevision = null;
		}

		if ( $revisionId > 0 && $entityRevision === null ) {
			// If a revision ID was specified, that revision doesn't exist or doesn't belong to
			// the given entity. Throw an error.
			throw new StorageException( "No such revision found for $entityId: $revisionId" );
		}

		wfProfileOut( __METHOD__ );
		return $entityRevision;
	}

	/**
	 * @since 0.4
	 * @see   EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @throws StorageException
	 * @return bool
	 */
	public function hasEntity( EntityId $entityId ) {
		$row = $this->loadPageRow( $entityId );

		return $row !== null;
	}

	/**
	 * Returns the id of the latest revision of the given entity, or false if there is no such entity.
	 *
	 * @since 0.5
	 *
	 * @param EntityId $entityId
	 *
	 * @return int|false
	 */
	public function getLatestRevisionId( EntityId $entityId ) {
		$row = $this->loadPageRow( $entityId );

		return $row === null ? false : $row->page_latest;
	}

	/**
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @throws DBQueryError
	 * @return object|null
	 */
	private function loadRevisionRow( EntityId $entityId, $revisionId ) {
		$row = $this->selectRevisionRow( $entityId, $revisionId );

		if ( !$row ) {
			// try loading from master
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load ' . $entityId
				. " with $revisionId from DB_MASTER." );

			$row = $this->selectRevisionRow( $entityId, $revisionId, DB_MASTER );
		}

		return $row;
	}

	/**
	 * Selects revision information from the page, revision, and text tables.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $revisionId The desired revision id, 0 means "current".
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return object|null a raw database row object, or null if no such entity revision exists.
	 */
	protected function selectRevisionRow( EntityId $entityId, $revisionId = 0, $connType = DB_SLAVE ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		$tables = array(
			'page',
			'revision',
			'text'
		);

		$pageTable = $db->tableName( 'page' );
		$revisionTable = $db->tableName( 'revision' );
		$textTable = $db->tableName( 'text' );

		$vars = "$pageTable.*, $revisionTable.*, $textTable.*";

		$where = array();
		$join = array();

		if ( $revisionId > 0 ) {
			// pick revision by id
			$where['rev_id'] = $revisionId;

			// pick page via rev_page
			$join['page'] = array( 'INNER JOIN', 'page_id=rev_page' );

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revisionId of " . $entityId );
		} else {
			// entity to page mapping
			$tables[] = 'wb_entity_per_page';

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

		$res = $db->select( $tables, $vars, $where, __METHOD__, array(), $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		$row = $res->fetchObject();

		wfProfileOut( __METHOD__ );
		return $row ? $row : null;
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @throws DBQueryError
	 * @return object|null
	 */
	private function loadPageRow( EntityId $entityId ) {
		$row = $this->selectPageRow( $entityId );

		if ( !$row ) {
			// try to load from master
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load ' . $entityId
				. ' from DB_MASTER.' );

			$row = $this->selectPageRow( $entityId, DB_MASTER );
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
	protected function selectPageRow( EntityId $entityId, $connType = DB_SLAVE ) {
		wfProfileIn( __METHOD__ );
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

		$res = $db->select( $tables, '*', $where, __METHOD__, array(), $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			wfProfileOut( __METHOD__ );
			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		$row = $res->fetchObject();

		wfProfileOut( __METHOD__ );
		return $row ? $row : null;
	}

	/**
	 * @todo: migrate table away from using a numeric field & get rid of this function
	 *
	 * @param EntityId $entityId
	 *
	 * @return mixed
	 */
	protected function getProperEntityId( EntityId $entityId ) {
		return $this->idParser->parse( $entityId->getSerialization() );
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * This calls Revision::getRevisionText to resolve any additional indirections in getting
	 * to the actual blob data, like the "External Store" mechanism used by Wikipedia & co.
	 *
	 * @param Object $row a row object as expected Revision::getRevisionText(), that is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @throws MWContentSerializationException
	 * @return object[] list( EntityRevision|null $entityRevision, EntityRedirect|null $redirect ),
	 * with either $entityRevision or $redirect or both being null (but not both being non-null).
	 */
	private function loadEntity( $row ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ': Calling getRevisionText() on revision '
			. $row->rev_id . '.' );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = Revision::getRevisionText( $row, 'old_', $this->wiki );

		if ( $blob === false ) {
			// oops. something went wrong.
			wfWarn( 'Unable to load raw content blob for revision ' . $row->rev_id . '.' );
			wfProfileOut( __METHOD__ );
			return null;
		}

		$format = $row->rev_content_format;
		$redirect = $this->contentCodec->decodeRedirect( $blob, $format );

		if ( $redirect ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ': Found redirect to entity '
				. $redirect->getTargetId() . '.' );

			wfProfileOut( __METHOD__ );
			return array( null, $redirect );
		} else {
			$entity = $this->contentCodec->decodeEntity( $blob, $format );

			if ( !$entity ) {
				throw new MWContentSerializationException(
					'The serialized data contains neither an Entity nor an EntityRedirect!'
				);
			}

			$entityRevision = new EntityRevision( $entity, (int)$row->rev_id, $row->rev_timestamp );

			wfDebugLog( __CLASS__, __FUNCTION__ . ': Created entity ' . $entity->getId()
				. ' from revision blob.' );

			wfProfileOut( __METHOD__ );
			return array( $entityRevision, null );
		}
	}

}
