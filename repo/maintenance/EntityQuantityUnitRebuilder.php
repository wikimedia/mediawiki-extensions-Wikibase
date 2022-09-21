<?php

namespace Wikibase\Repo\Maintenance;

use DataValues\QuantityValue;
use DataValues\UnboundedQuantityValue;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Statement\StatementListProvider;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * @license GPL-2.0-or-later
 */
class EntityQuantityUnitRebuilder {
	/** @var SeekableEntityIdPager */
	private $idPager;
	/** @var MessageReporter */
	private $progressReporter;
	/** @var MessageReporter */
	private $errorReporter;
	/** @var RepoDomainDb */
	private $db;
	/** @var EntityLookup */
	private $entityLookup;
	/** @var int */
	private $batchSize;
	/** @var int */
	private $batchSpacingInSeconds;
	/** @var string */
	private $valueFrom;
	/** @var string */
	private $valueTo;
	/** @var EntityStore */
	private $entityStore;
	/** @var \User */
	private $performer;

	/**
	 * @param SeekableEntityIdPager $idPager
	 * @param MessageReporter $progressReporter
	 * @param MessageReporter $errorReporter
	 * @param RepoDomainDb $db
	 * @param EntityLookup $entityLookup
	 * @param int $batchSize
	 * @param int $batchSpacingInSeconds
	 */
	public function __construct(
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		RepoDomainDb $db,
		EntityLookup $entityLookup,
		int $batchSize,
		int $batchSpacingInSeconds,
		string $valueFrom,
		string $valueTo
	) {
		$this->idPager = $idPager;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->db = $db;
		$this->entityLookup = $entityLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
		$this->valueFrom = $valueFrom;
		$this->valueTo = $valueTo;
		$this->entityStore = WikibaseRepo::getEntityStore();
		$this->performer = \User::newSystemUser( \User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
	}

	public function rebuild() {
		$ticket = $this->db->getEmptyTransactionTicket( __METHOD__ );

		$counter = 0;
		while ( true ) {
			$entityIds = $this->idPager->fetchIds( $this->batchSize );
			$numEntities = count( $entityIds );

			if ( $numEntities == 0 ) {
				break;
			}

			$this->rebuildEntityQuantityForUnit( $entityIds );

			$success = $this->db->commitAndWaitForReplication( __METHOD__, $ticket );

			$counter += $numEntities;
			$this->progressReporter->reportMessage(
				'Processed ' . $counter . ' entities (' . end( $entityIds ) . ')'
			);

			if ( !$success ) {
				$this->errorReporter->reportMessage(
					'commitAndWaitForReplication() timed out, aborting'
				);
				break;
			}

			if ( $this->batchSpacingInSeconds > 0 ) {
				sleep( $this->batchSpacingInSeconds );
			}
		}
	}

	/**
	 * @param EntityId[] $entityIds
	 */
	private function rebuildEntityQuantityForUnit( array $entityIds ) {
		foreach ( $entityIds as $entityId ) {
			$this->updateQuantityUnit(
				$this->entityLookup->getEntity( $entityId )
			);
		}
	}

	private function updateQuantityUnit( EntityDocument $entity ) {
		$updateCounter = 0;

		if ( !$entity instanceof StatementListProvider ) {
			$this->errorReporter->reportMessage(
				$entity->getId()->getSerialization() . ' failed to update because it is not a StatementListProvider'
			);
			return;
		}

		/** @var StatementList $statements */
		$statements = $entity->getStatements();
		foreach ( $statements->toArray() as $statement ) {

			$mainSnak = $statement->getMainSnak();

			if ( !$mainSnak instanceof PropertyValueSnak ) {
				continue;
			}

			$value = $mainSnak->getDataValue()->getValue();
			if ( $value instanceof UnboundedQuantityValue ) {
				$unit = $value->getUnit();

				if ( str_contains( $unit, $this->valueFrom ) ) {
					$newUnit = str_replace( $this->valueFrom, $this->valueTo, $unit );

					if ( $value instanceof QuantityValue ) {
						$value = QuantityValue::newFromNumber(
							$value->getAmount(),
							$newUnit,
							$value->getUpperBound(),
							$value->getLowerBound()
						);
					} else {
						$value = UnboundedQuantityValue::newFromNumber( $value->getAmount(), $newUnit );
					}

					$mainSnak = new PropertyValueSnak( $mainSnak->getPropertyId(), $value );
					$updateCounter++;
				}
			}

			$statement->setMainSnak( $mainSnak );
		}

		if ( $updateCounter > 0 ) {
			$rev = $this->entityStore->saveEntity( $entity,
				"T312256: Updating quantity unit",
				$this->performer,
				EDIT_UPDATE
			);

			$this->progressReporter->reportMessage(
				"Updating {$entity->getId()}: revision: {$rev->getRevisionId()} updates: {$updateCounter}"
			);
		}
	}
}
