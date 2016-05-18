<?php

namespace Wikibase\Lib\Store\Sql;

use DatabaseBase;
use DBAccessBase;
use DBQueryError;
use ResultWrapper;
use stdClass;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;

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
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string|bool $wiki The name of the wiki database to use (use false for the local wiki)
	 */
	public function __construct(
		EntityNamespaceLookup $entityNamespaceLookup,
		$wiki = false
	) {
		parent::__construct( $wiki );
		$this->entityNamespaceLookup = $entityNamespaceLookup;
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

		$join = [];
		$fields = $this->selectFields();
		// To be able to link rows with entity ids
		$fields[] = 'page_title';

		$tables = [ 'page', 'revision', 'text' ];

		// pick latest revision via page_latest
		$join['revision'] = [ 'INNER JOIN', 'page_latest=rev_id' ];

		// pick text via rev_text_id
		$join['text'] = [ 'INNER JOIN', 'old_id=rev_text_id' ];

		$res = $db->select( $tables, $fields, $this->getWhere( $entityIds, $db ), __METHOD__, [], $join );

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
		$rows = [];
		// Create a key based map from the rows just returned to reduce
		// the complexity below.
		foreach ( $res as $row ) {
			$rows[$row->page_title] = $row;
		}

		$result = [];
		foreach ( $entityIds as $entityId ) {
			$entityId = $entityId->getSerialization();

			$result[$entityId] = false;

			if ( isset( $rows[$entityId] ) ) {
				$result[$entityId] = $rows[$entityId];
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
	private function getWhere( array $entityIds, DatabaseBase $db ) {
		$where = [];

		foreach ( $entityIds as $entityId ) {
			$where[] = $db->makeList(
				[
					$db->addQuotes( $entityId->getSerialization() ) . '=page_title',
					$this->entityNamespaceLookup->getEntityNamespace( $entityId->getEntityType() ) . '=page_namespace'
				],
				LIST_AND
			);
		}

		return $db->makeList( $where, LIST_OR );
	}

}
