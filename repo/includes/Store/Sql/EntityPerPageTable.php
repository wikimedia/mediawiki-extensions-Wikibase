<?php

namespace Wikibase\Repo\Store\Sql;

use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Repo\Store\EntityPerPage;
use Wikimedia\Rdbms\LoadBalancer;

/**
 * Represents a lookup database table that makes the link between entities and pages.
 * Corresponds to the wb_entities_per_page table.
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Daniel Kinzler
 */
class EntityPerPageTable implements EntityPerPage {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var LoadBalancer
	 */
	private $loadBalancer;

	/**
	 * @param LoadBalancer $loadBalancer
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct(
		LoadBalancer $loadBalancer,
		EntityIdParser $entityIdParser
	) {
		$this->loadBalancer = $loadBalancer;
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @see EntityPerPage::addEntityPage
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 *
	 * @throws InvalidArgumentException
	 */
	public function addEntityPage( EntityId $entityId, $pageId ) {
		$this->addRow( $entityId, $pageId );
	}

	/**
	 * @see EntityPerPage::addEntityPage
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 * @param EntityId $targetId
	 */
	public function addRedirectPage( EntityId $entityId, $pageId, EntityId $targetId ) {
		$this->addRow( $entityId, $pageId, $targetId );
	}

	/**
	 * @see EntityPerPage::addEntityPage
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 * @param EntityId|null $targetId
	 *
	 * @throws InvalidArgumentException
	 */
	private function addRow( EntityId $entityId, $pageId, EntityId $targetId = null ) {
		if ( !( $entityId instanceof Int32EntityId ) ) {
			throw new InvalidArgumentException( '$entityId must be an Int32EntityId' );
		}
		if ( !is_int( $pageId ) ) {
			throw new InvalidArgumentException( '$pageId must be an int' );
		}
		if ( $pageId <= 0 ) {
			throw new InvalidArgumentException( '$pageId must be greater than 0' );
		}

		$redirectTarget = $targetId ? $targetId->getSerialization() : null;

		$values = array(
			'epp_entity_id' => $entityId->getNumericId(),
			'epp_entity_type' => $entityId->getEntityType(),
			'epp_page_id' => $pageId,
			'epp_redirect_target' => $redirectTarget
		);

		if ( !$this->rowExists( $values ) ) {
			$this->addRowInternal( $values );
		}
	}

	/**
	 * @param array $row
	 */
	private function addRowInternal( array $row ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );
		// Try to add the row and see it it conflicts on (id,type) or (page_id).
		// With innodb, this only sets IX gap and SH/EX record locks. This is useful for new
		// page/entity creation, as just doing DELETE+INSERT would put SH gap locks on the range
		// [highest page/entity ID, +infinity). Aside from serializing page creation, any case
		// where 2+ such transactions made it past DELETE would deadlock on IX/SH gap locks.
		$dbw->insert(
			'wb_entity_per_page',
			$row,
			__METHOD__,
			[ 'IGNORE' ]
		);
		if ( $dbw->affectedRows() > 0 ) {
			return; // no conflicts
		}

		// Delete the conflicting rows...
		$conflictConds = $this->getConflictingRowConditions( $row );
		$where = $dbw->makeList( $conflictConds, LIST_OR );
		$dbw->delete(
			'wb_entity_per_page',
			$where,
			__METHOD__
		);
		// ...and try to insert again
		$dbw->insert(
			'wb_entity_per_page',
			$row,
			__METHOD__
		);
	}

	/**
	 * @param array $row
	 *
	 * @return bool
	 */
	private function rowExists( array $row ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		return $dbw->selectRow( 'wb_entity_per_page', '1', $row, __METHOD__ ) !== false;
	}

	private function getConflictingRowConditions( array $values ) {
		$dbw = $this->loadBalancer->getConnection( DB_MASTER );
		$indexes = $this->getUniqueIndexes();

		$conditions = array();

		foreach ( $indexes as $indexFields ) {
			$indexValues = array_intersect_key( $values, array_flip( $indexFields ) );
			$conditions[] = $dbw->makeList( $indexValues, LIST_AND );
		}

		return $conditions;
	}

	/**
	 * Returns a list of unique indexes, each index being described by a list of fields.
	 * This is intended for use with DatabaseBase::replace().
	 *
	 * @return array[]
	 */
	private function getUniqueIndexes() {
		// CREATE UNIQUE INDEX /*i*/wb_epp_entity ON /*_*/wb_entity_per_page (epp_entity_id, epp_entity_type);
		// CREATE UNIQUE INDEX /*i*/wb_epp_page ON /*_*/wb_entity_per_page (epp_page_id);

		return array(
			'wb_epp_entity' => array( 'epp_entity_id', 'epp_entity_type' ),
			'wb_epp_page' => array( 'epp_page_id' ),
		);
	}

	/**
	 * @see EntityPerPage::deleteEntityPage
	 *
	 * @param EntityId $entityId
	 * @param int $pageId
	 *
	 * @return boolean Success indicator
	 */
	public function deleteEntityPage( EntityId $entityId, $pageId ) {
		$this->deleteEntity( $entityId );
	}

	/**
	 * @param EntityId $entityId
	 *
	 * @return boolean
	 */
	public function deleteEntity( EntityId $entityId ) {
		if ( !( $entityId instanceof Int32EntityId ) ) {
			throw new InvalidArgumentException( '$entityId must be an Int32EntityId' );
		}

		$dbw = $this->loadBalancer->getConnection( DB_MASTER );

		return $dbw->delete(
			'wb_entity_per_page',
			array(
				'epp_entity_id' => $entityId->getNumericId(),
				'epp_entity_type' => $entityId->getEntityType()
			),
			__METHOD__
		);
	}

	/**
	 * @see EntityPerPage::clear
	 *
	 * @return boolean Success indicator
	 */
	public function clear() {
		return $this->loadBalancer->getConnection( DB_MASTER )->delete( 'wb_entity_per_page', '*', __METHOD__ );
	}

}
