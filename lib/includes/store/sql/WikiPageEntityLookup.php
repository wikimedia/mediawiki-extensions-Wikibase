<?php

namespace Wikibase\Lib\Store;

use DBQueryError;
use Deserializers\Exceptions\DeserializationException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\EntityRevision;
use Wikibase\StorageException;

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
class WikiPageEntityLookup extends \DBAccessBase implements EntityRevisionLookup {

	/**
	 * @var EntityIdParser
	 */
	protected $idParser;

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		$wiki = false
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		// TODO: migrate table away from using a numeric field so we no longer need this!
		$this->idParser = new BasicEntityIdParser();
	}

	/**
	 * @see EntityLookup::getEntity
	 *
	 * @param EntityId $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return Entity|null
	 *
	 * @throw StorageException
	 */
	public function getEntity( EntityId $entityId, $revision = 0 ) {
		$entityRev = $this->getEntityRevision( $entityId, $revision );
		return $entityRev === null ? null : $entityRev->getEntity();
	}

	/**
	 * @since 0.4
	 * @see   EntityRevisionLookup::getEntityRevision
	 *
	 * @param EntityId $entityId
	 * @param int $revision The desired revision id, 0 means "current".
	 *
	 * @return EntityRevision|null
	 * @throws StorageException
	 */
	public function getEntityRevision( EntityId $entityId, $revision = 0 ) {
		wfProfileIn( __METHOD__ );
		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up entity " . $entityId
				. " (rev $revision)" );

		if ( $revision === false ) { // default changed from false to 0
			wfWarn( 'getEntityRevision() called with $revision = false, use 0 instead.' );
			$revision = 0;
		}

		$row = $this->loadRevisionRow( $entityId, $revision );

		if ( $row ) {
			$entityRev = $this->loadEntity( $row );

			if ( !$entityRev ) {
				// This only happens when there is a problem with the external store.
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Entity not loaded for " . $entityId );
			}
		} else {
			// No such revision
			$entityRev = null;
		}

		if ( $entityRev && !$entityId->equals( $entityRev->getEntity()->getId() ) ) {
			// This can happen when giving a revision ID that doesn't belong to the given entity
			wfDebugLog( __CLASS__, __FUNCTION__ . ": Loaded wrong entity: expected " . $entityId
							. ", got " . $entityRev->getEntity()->getId());

			$entityRev = null;
		}

		if ( $entityRev === null && $revision > 0 ) {
			// If a revision was specified, that revision doesn't exist or doesn't belong to
			// the given entity. Throw an error.
			throw new StorageException( "No such revision found for $entityId: $revision" );
		}

		wfProfileOut( __METHOD__ );
		return $entityRev;
	}

	/**
	 * @since 0.4
	 * @see   EntityLookup::hasEntity
	 *
	 * @param EntityId $entityId
	 *
	 * @return bool
	 * @throws StorageException
	 */
	public function hasEntity( EntityId $entityId ) {
		$row = $this->loadPageRow( $entityId );

		return ( $row !== null );
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
	 * @param int $revision
	 *
	 * @throws DBQueryError
	 * @return object|null
	 */
	private function loadRevisionRow( EntityId $entityId, $revision ) {
		$row = $this->selectRevisionRow( $entityId, $revision );

		if ( !$row ) {
			// try loading from master
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load '
				. $entityId->getSerialization() . "with $revision from DB_MASTER." );

			$row = $this->selectRevisionRow( $entityId, $revision, DB_MASTER );
		}

		return $row;
	}

	/**
	 * Selects revision information from the page, revision, and text tables.
	 *
	 * @since 0.4
	 *
	 * @param EntityId $entityId The entity to query the DB for.
	 * @param int $revision The desired revision id, 0 means "current".
	 * @param int $connType DB_READ or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return object|null a raw database row object, or null if no such entity revision exists.
	 */
	protected function selectRevisionRow( EntityId $entityId, $revision = 0, $connType = DB_READ ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		$opt = array();

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

		if ( $revision > 0 ) {
			// pick revision by id
			$where['rev_id'] = $revision;

			// pick page via rev_page
			$join['page'] = array( 'INNER JOIN', 'page_id=rev_page' );

			// pick text via rev_text_id
			$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revision of " . $entityId );
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

			wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up latest revision of " . $entityId );
		}

		$res = $db->select( $tables, $vars, $where, __METHOD__, $opt, $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();
			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		if ( $row = $res->fetchObject() ) {
			wfProfileOut( __METHOD__ );
			return $row;
		} else {
			wfProfileOut( __METHOD__ );
			return null;
		}
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
			wfDebugLog(  __CLASS__, __FUNCTION__ . ': try to load '
				. $entityId->getSerialization() . ' from DB_MASTER.' );

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
	 * @param boolean $connType DB_READ or DB_MASTER
	 *
	 * @throws \DBQueryError If the query fails.
	 * @return object|null a raw database row object, or null if no such entity revision exists.
	 */
	protected function selectPageRow( EntityId $entityId, $connType = DB_READ ) {
		wfProfileIn( __METHOD__ );
		$db = $this->getConnection( $connType );

		$tables = array(
			'page',
			'wb_entity_per_page',
		);

		$where = array();
		$join = array();
		$opt = array();

		$entityId = $this->getProperEntityId( $entityId );

		// pick entity by id
		$where['epp_entity_id'] = $entityId->getNumericId();
		$where['epp_entity_type'] = $entityId->getEntityType();

		// pick page via epp_page_id
		$join['page'] = array( 'INNER JOIN', 'epp_page_id=page_id' );

		$res = $db->select( $tables, '*', $where, __METHOD__, $opt, $join );

		if ( !$res ) {
			// this can only happen if the DB is set to ignore errors, which shouldn't be the case...
			$error = $db->lastError();
			$errno = $db->lastErrno();

			wfProfileOut( __METHOD__ );
			throw new DBQueryError( $db, $error, $errno, '', __METHOD__ );
		}

		$this->releaseConnection( $db );

		if ( $row = $res->fetchObject() ) {
			wfProfileOut( __METHOD__ );
			return $row;
		} else {
			wfProfileOut( __METHOD__ );
			return null;
		}
	}

	/**
	 * @todo: migrate table away from using a numeric field & get rid of this function
	 *
	 * @param EntityId $id
	 *
	 * @return mixed
	 */
	protected function getProperEntityId( EntityId $id ) {
		return $this->idParser->parse( $id->getSerialization() );
	}

	/**
	 * Construct an EntityRevision object from a database row from the revision and text tables.
	 *
	 * This calls Revision::getRevisionText to resolve any additional indirections in getting
	 * to the actual blob data, like the "External Store" mechanism used by Wikipedia & co.
	 *
	 * @param Object $row a row object as expected \Revision::getRevisionText(), that is, it
	 *        should contain the relevant fields from the revision and/or text table.
	 *
	 * @return EntityRevision
	 */
	private function loadEntity( $row ) {
		wfProfileIn( __METHOD__ );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": calling getRevisionText() on rev " . $row->rev_id );

		//NOTE: $row contains revision fields from another wiki. This SHOULD not
		//      cause any problems, since getRevisionText should only look at the old_flags
		//      and old_text fields. But be aware.
		$blob = \Revision::getRevisionText( $row, 'old_', $this->wiki );

		if ( $blob === false ) {
			// oops. something went wrong.
			wfWarn( "Unable to load raw content blob for rev " . $row->rev_id );
			wfProfileOut( __METHOD__ );
			return null;
		}

		$format = $row->rev_content_format;
		$entity = $this->unserializeEntity( $blob, $format );
		$entityRev = new EntityRevision( $entity, (int)$row->rev_id, $row->rev_timestamp );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Created entity object from revision blob: "
			. $entity->getId() );

		wfProfileOut( __METHOD__ );
		return $entityRev;
	}

	/**
	 * @see ContentHandler::unserializeContent
	 *
	 * @since 0.5
	 *
	 * @param string $blob
	 * @param null|string $format
	 *
	 * @return Entity|null
	 * @throws StorageException
	 */
	private function unserializeEntity( $blob, $format = null ) {
		$entity = $this->contentCodec->decodeEntity( $blob, $format );

		return $entity;
	}

}
