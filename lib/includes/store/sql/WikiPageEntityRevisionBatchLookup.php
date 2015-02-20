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

/**
 * Implements an entity repo based on blobs stored in wiki pages on a locally reachable
 * database server.
 * @todo This should share code with WikiPageEntityRevisionLookup where possible
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 * @author Daniel Kinzler
 */
class WikiPageEntityRevisionBatchLookup extends DBAccessBase implements EntityRevisionBatchLookup {

	/**
	 * Flag to use instead of an revision ID to indicate that the latest revision is desired,
	 * but a slightly lagged version is acceptable. This would generally be the case when fetching
	 * entities for display.
	 */
	const LATEST_FROM_SLAVE = 'slave';

	/**
	 * Flag to use instead of an revision ID to indicate that the latest revision is desired,
	 * and it is essential to assert that there really is no newer version, to avoid data loss
	 * or conflicts. This would generally be the case when loading an entity for
	 * editing/modification.
	 */
	const LATEST_FROM_MASTER = 'master';

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityContentDataCodec
	 */
	private $contentCodec;

	/**
	 * @var string (self::LATEST_FROM_SLAVE or self::LATEST_FROM_MASTER)
	 */
	private $mode;

	/**
	 * @param EntityContentDataCodec $contentCodec
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 * @param string $mode LATEST_FROM_SLAVE or LATEST_FROM_MASTER. LATEST_FROM_MASTER would force the
	 *        revision information to be determined from the canonical master database.
	 */
	public function __construct(
		EntityContentDataCodec $contentCodec,
		EntityIdParser $entityIdParser,
		$wiki = false,
		$mode = self::LATEST_FROM_SLAVE
	) {
		parent::__construct( $wiki );

		$this->contentCodec = $contentCodec;

		// TODO: migrate table away from using a numeric field so we no longer need this!
		$this->entityIdParser = $entityIdParser;

		$this->mode = $mode;
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
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return array entityid -> EntityRevision, EntityRedirect or null (if not found)
	 */
	public function getEntityRevisions( array $entityIds ) {
		if ( empty( $entityIds ) ) {
			return array();
		}

		$entityIds = $this->getProperEntityIds( $entityIds );

		$rows = $this->loadRevisionInformation( $entityIds );

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
	 * @param EntityId[] $entityIds
	 *
	 * @throws DBQueryError
	 * @return array entityid -> object or false (if not found)
	 */
	private function loadRevisionInformation( array $entityIds ) {
		$rows = array();

		if ( $this->mode !== self::LATEST_FROM_MASTER ) {
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
	 * Returns an array like entityid -> object or false (if not found)
	 *
	 * @param EntityId[] $entityIds The entity to query the DB for.
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
	 * @param EntityId[] $entityIds
	 *
	 * @return EntityId[]
	 */
	private function getProperEntityIds( array $entityIds ) {
		foreach ( $entityIds as &$entityId ) {
			$entityId = $this->entityIdParser->parse( $entityId->getSerialization() );
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
