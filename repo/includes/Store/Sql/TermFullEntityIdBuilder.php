<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Int32EntityId;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Reporting\ObservableMessageReporter;
use Wikimedia\Rdbms\IDatabase;
use Wikimedia\Rdbms\IResultWrapper;
use Wikimedia\Rdbms\LBFactory;

class TermFullEntityIdBuilder {

	const TABLE_NAME = 'wb_terms';

	private $loadBalancerFactory;

	private $entityIdComposer;

	private $entityIdParser;

	private $reporter;

	private $batchSize;

	private $rebuildAll;

	/**
	 * @var Int32EntityId|null
	 */
	private $selectFromId = null;

	public function __construct(
		LBFactory $loadBalancerFactory,
		EntityIdComposer $entityIdComposer,
		EntityIdParser $entityIdParser,
		ObservableMessageReporter $reporter,
		$batchSize = 1000,
		$rebuildAll = false
	) {
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->entityIdComposer = $entityIdComposer;
		$this->entityIdParser = $entityIdParser;
		$this->reporter = $reporter;
		$this->batchSize = $batchSize;
		$this->rebuildAll = $rebuildAll;
	}

	public function rebuild() {
		$entityTypes = $this->getEntityTypes();

		foreach ( $entityTypes as $entityType ) {
			$this->rebuildForEntityType( $entityType );
		}

		$this->reporter->reportMessage( 'Done' );
	}

	/**
	 * @param string $entityType
	 */
	private function rebuildForEntityType( $entityType ) {
		$this->selectFromId = $this->getStartEntityId();

		while ( true ) {
			$dbw = $this->loadBalancerFactory->getMainLB()->getConnection( DB_MASTER );
			$rows = $this->selectBatch( $dbw, $entityType );

			if ( $rows->numRows() === 0 ) {
				break;
			}

			$this->selectFromId = $this->updateBatch( $dbw, $rows );

			$serializedId = $this->selectFromId->getSerialization();
			$this->reporter->reportMessage( "Updated to $serializedId" );
		}
	}

	/**
	 * @param string $entityType
	 * @return EntityId
	 */
	private function getStartEntityId( $entityType ) {
		$dbw = $this->loadBalancerFactory->getMainLB()->getConnection( DB_MASTER );

		$conds = [
			'term_entity_type' => $entityType
		];

		if ( $this->rebuildAll === false ) {
			$conds[] = 'term_full_entity_id IS NULL';
		}

		$row = $dbw->selectRow(
			self::TABLE_NAME,
			[ 'term_entity_id', 'term_entity_type' ],
			$conds,
			__METHOD__,
			[
				'GROUP BY' => [ 'term_entity_id', 'term_entity_type' ],
			    'ORDER BY' => [ 'term_entity_id', 'term_entity_type' ],
			    'LIMIT' => 1
			]
		);

		$entityId = $this->entityIdComposer->composeEntityId(
			'',
			$row->term_entity_type,
			$row->term_entity_id
		);

		return $entityId;
	}

	/**
	 * @param IDatabase $dbw
	 * @param IResultWrapper $rows
	 * @return void|EntityId
	 */
	private function updateBatch( IDatabase $dbw, IResultWrapper $rows ) {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );
		$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

		$dbw->startAtomic( __METHOD__ );

		foreach ( $rows as $row ) {
			$entityId = $this->entityIdComposer->composeEntityId(
				'',
				$row->term_entity_type,
				$row->term_entity_id
			);

			$dbw->update(
				self::TABLE_NAME,
				[ 'term_full_entity_id' => $entityId->getSerialization() ],
				[
					'term_entity_id' => $row->term_entity_id,
					'term_entity_type' => $row->term_entity_type,
				],
				__METHOD__
			);

		}

		$dbw->endAtomic( __METHOD__ );

		return $entityId;
	}

	/**
	 * @param IDatabase $dbw
	 * @param string $entityType
	 * @return bool|IResultWrapper
	 */
	private function selectBatch( IDatabase $dbw, $entityType ) {
		$selectColumns = [ 'term_entity_id', 'term_entity_type' ];

		$conds = [
			'term_entity_type' => $entityType
		];

		if ( $this->rebuildAll === false ) {
			$conds[] = "term_full_entity_id IS NULL";
		}


		$conds[] = "term_entity_id >= " . $this->selectFromId->getNumericId();

		$rows = $dbw->select(
			self::TABLE_NAME,
			$selectColumns,
			$conds,
			__METHOD__,
			[
				'GROUP_BY' => [ 'term_entity_id' ],
				'ORDER BY' => [ 'term_entity_id' ],
				'LIMIT' => $this->batchSize
			]
		);

		return $rows;
	}

	/**
	 * @return string[]
	 */
	private function getEntityTypes() {
		$dbw = $this->loadBalancerFactory->getMainLB()->getConnection( DB_MASTER );
		$rows = $dbw->select( self::TABLE_NAME, 'DISTINCT term_entity_type', [], __METHOD__ );

		$entityTypes = [];

		foreach ( $rows as $row ) {
			$entityTypes[] = $row->term_entity_type;
		}

		return $entityTypes;
	}

}
