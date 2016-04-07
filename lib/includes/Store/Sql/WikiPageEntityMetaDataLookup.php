<?php

namespace Wikibase\Lib\Store\Sql;

use DatabaseBase;
use DBAccessBase;
use DBQueryError;
use ResultWrapper;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityRevisionLookup;

/**
 * Service for looking up meta data about one or more entities as needed for
 * loading entities from WikiPages (via Revision) or to verify an entity against
 * page.page_latest.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch < hoo@online.de >
 */
class WikiPageEntityMetaDataLookup extends DBAccessBase implements WikiPageEntityMetaDataAccessor {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		$wiki = false
	) {
		parent::__construct( $wiki );

		// TODO: migrate table away from using a numeric field so we no longer need this!
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param string $mode (EntityRevisionLookup::LATEST_FROM_SLAVE or EntityRevisionLookup::LATEST_FROM_MASTER)
	 *
	 * @throws DBQueryError
	 * @return stdClass[] Array of entity id serialization => object.
	 */
	public function loadRevisionInformation( array $entityIds, $mode ) {
		$rows = array();

		if ( $mode !== EntityRevisionLookup::LATEST_FROM_MASTER ) {
			$rows = $this->selectRevisionInformationMultiple( $entityIds, DB_SLAVE );
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
				$this->selectRevisionInformationMultiple( $loadFromMaster, DB_MASTER )
			);
		}

		return $rows;
	}

	/**
	 * @param EntityId $entityId
	 * @param int $revisionId
	 *
	 * @throws DBQueryError
	 * @return stdClass|bool
	 */
	public function loadRevisionInformationByRevisionId( EntityId $entityId, $revisionId ) {
		$row = $this->selectRevisionInformationById( $entityId, $revisionId, DB_SLAVE );

		if ( !$row ) {
			// Try loading from master
			wfDebugLog( __CLASS__, __FUNCTION__ . ': try to load ' . $entityId
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
			'page_latest',
			'page_is_redirect',
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
	 * @return stdClass|bool a raw database row object, or false if no such entity revision exists.
	 */
	private function selectRevisionInformationById( EntityId $entityId, $revisionId, $connType ) {
		$db = $this->getConnection( $connType );

		$join = array();

		// pick text via rev_text_id
		$join['text'] = array( 'INNER JOIN', 'old_id=rev_text_id' );
		$join['page'] = array( 'INNER JOIN', 'rev_page=page_id' );

		wfDebugLog( __CLASS__, __FUNCTION__ . ": Looking up revision $revisionId of " . $entityId );

		$row = $db->selectRow(
			array( 'revision', 'text', 'page' ),
			$this->selectFields(),
			array( 'rev_id' => $revisionId ),
			__METHOD__,
			array(),
			$join
		);

		$this->releaseConnection( $db );

		return $row;
	}

	/**
	 * Selects revision information from the page, revision, and text tables.
	 * Returns an array like entityid -> object or false (if not found).
	 *
	 * @param EntityId[] $entityIds The entities to query the DB for.
	 * @param int $connType DB_SLAVE or DB_MASTER
	 *
	 * @throws DBQueryError If the query fails.
	 * @return stdClass[] Array of entity id serialization => object.
	 */
	private function selectRevisionInformationMultiple( array $entityIds, $connType ) {
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

		$this->releaseConnection( $db );

		return $this->indexResultByEntityId( $entityIds, $res );
	}

	/**
	 * Takes a ResultWrapper and indexes the returned rows based on the serialized
	 * entity id of the entities they refer to.
	 *
	 * @param EntityId[] $entityIds
	 * @param ResultWrapper $res
	 *
	 * @return stdClass[] Array of entity id serialization => object.
	 */
	private function indexResultByEntityId( array $entityIds, ResultWrapper $res ) {
		$rows = array();
		// Create a key based map from the rows just returned to reduce
		// the complexity below.
		foreach ( $res as $row ) {
			$rows[$row->epp_entity_type . $row->epp_entity_id] = $row;
		}

		$result = array();
		foreach ( $entityIds as $entityId ) {
			$result[$entityId->getSerialization()] = false;

			// FIXME: this will fail for IDs that do not have a numeric form
			$key = $entityId->getEntityType() . $entityId->getNumericId();
			if ( isset( $rows[$key] ) ) {
				$result[$entityId->getSerialization()] = $rows[$key];
			}
		}

		return $result;
	}

	/**
	 * @param EntityId[] $entityIds
	 * @param DatabaseBase $db
	 *
	 * @return string
	 */
	private function getEppWhere( array $entityIds, DatabaseBase $db ) {
		$where = array();

		foreach ( $entityIds as &$entityId ) {
			$where[] = $db->makeList( array(
				// FIXME: this will fail for IDs that do not have a numeric form
				// Note: if epp_entity_id is quoted the wrong index will be used
				//       thus we cast it to int and leave it unquoted
				'epp_entity_id = ' . (int)$entityId->getNumericId(),
				'epp_entity_type' => $entityId->getEntityType()
			), LIST_AND );
		}

		return $db->makeList( $where, LIST_OR );
	}

}
