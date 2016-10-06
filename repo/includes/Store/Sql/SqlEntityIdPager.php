<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Repo\Store\EntityIdPager;

/**
 * SqlEntityIdPager is a cursor for iterating over the EntityIds stored in
 * the current Wikibase installation.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class SqlEntityIdPager implements EntityIdPager {

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var string|null
	 */
	private $entityType;

	/**
	 * @var EntityId|null
	 */
	private $position = null;

	/**
	 * @var string
	 */
	private $redirectMode;

	/**
	 * @param EntityIdComposer $entityIdComposer
	 * @param null|string $entityType The desired entity type, or null for any type.
	 * @param string $redirectMode A EntityIdPager::XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 */
	public function __construct(
		EntityIdComposer $entityIdComposer,
		$entityType = null,
		$redirectMode = EntityIdPager::NO_REDIRECTS
	) {
		$this->entityIdComposer = $entityIdComposer;
		$this->entityType = $entityType;
		$this->redirectMode = $redirectMode;
	}

	/**
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note: After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @since 0.5
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit ) {
		$ids = $this->listEntities( $this->entityType, $limit, $this->position, $this->redirectMode );

		if ( !empty( $ids ) ) {
			$this->position = end( $ids );
			reset( $ids );
		}

		return $ids;
	}

	/**
	 * @param null|string $entityType The entity type to look for.
	 * @param int $limit The maximum number of IDs to return.
	 * @param EntityId|null $after Only return entities with IDs greater than this.
	 * @param string $redirects A XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 *
	 * @throws InvalidArgumentException
	 * @return EntityId[]
	 */
	private function listEntities( $entityType, $limit, EntityId $after = null, $redirects = self::NO_REDIRECTS ) {
		if ( $entityType === null ) {
			$where = array();
			//NOTE: needs to be id/type, not type/id, according to the definition of the relevant
			//      index in wikibase.sql: wb_entity_per_page (epp_entity_id, epp_entity_type);
			$orderBy = array( 'epp_entity_id', 'epp_entity_type' );
		} elseif ( !is_string( $entityType ) ) {
			throw new InvalidArgumentException( '$entityType must be a string (or null)' );
		} else {
			$where = array( 'epp_entity_type' => $entityType );
			// NOTE: If the type is fixed, don't use the type in the order;
			// before changing this, check index usage.
			$orderBy = array( 'epp_entity_id' );
		}

		if ( $redirects === self::NO_REDIRECTS ) {
			$where[] = 'epp_redirect_target IS NULL';
		} elseif ( $redirects === self::ONLY_REDIRECTS ) {
			$where[] = 'epp_redirect_target IS NOT NULL';
		}

		if ( !is_int( $limit ) || $limit < 1 ) {
			throw new InvalidArgumentException( '$limit must be a positive integer' );
		}

		$dbr = wfGetDB( DB_REPLICA );

		if ( $after ) {
			if ( !( $after instanceof Int32EntityId ) ) {
				throw new InvalidArgumentException( '$after must be an Int32EntityId' );
			}

			$numericId = (int)$after->getNumericId();

			if ( $entityType === null ) {
				// Ugly. About time we switch to qualified, string based IDs!
				// NOTE: this must be consistent with the sort order, see above!
				$where[] = '( ( epp_entity_type > ' . $dbr->addQuotes( $after->getEntityType() )
					. ' AND epp_entity_id = ' . $numericId . ' )'
					. ' OR epp_entity_id > ' . $numericId . ' )';
			} else {
				$where[] = 'epp_entity_id > ' . $numericId;
			}
		}

		$rows = $dbr->select(
			'wb_entity_per_page',
			array( 'entity_type' => 'epp_entity_type', 'entity_id' => 'epp_entity_id' ),
			$where,
			__METHOD__,
			array(
				'ORDER BY' => $orderBy,
				// MySQL tends to use the epp_redirect_target key which has a very low selectivity
				'USE INDEX' => 'wb_epp_entity',
				'LIMIT' => $limit
			)
		);

		$ids = $this->getEntityIdsFromRows( $rows );
		return $ids;
	}

	private function getEntityIdsFromRows( $rows ) {
		$entities = array();

		foreach ( $rows as $row ) {
			try {
				$entities[] = $this->entityIdComposer->composeEntityId( $row->entity_type, $row->entity_id );
			} catch ( InvalidArgumentException $ex ) {
				wfLogWarning( 'Unsupported entity type "' . $row->entity_type . '"' );
			}
		}

		return $entities;
	}

}
