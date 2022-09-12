<?php

namespace Wikibase\Repo\Maintenance;

use DataValues\UnboundedQuantityValue;
use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Lib\Rdbms\RepoDomainDb;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\DataModel\Entity\NumericPropertyId;

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
	/** @var PropertyLookup */
	private $propertyLookup;
	/** @var ItemLookup */
	private $itemLookup;
	/** @var int */
	private $batchSize;
	/** @var int */
	private $batchSpacingInSeconds;
	/** @var string */
	private $hostFrom;
	/** @var string */
	private $hostTo;
	private $entityStore;
	private $performer;

	/** @var bool */
	private $all;

	/**
	 * @param PropertyTermStoreWriter $propertyTermStoreWriter
	 * @param SeekableEntityIdPager $idPager
	 * @param MessageReporter $progressReporter
	 * @param MessageReporter $errorReporter
	 * @param RepoDomainDb $db
	 * @param PropertyLookup $propertyLookup
	 * @param int $batchSize
	 * @param int $batchSpacingInSeconds
	 */
	public function __construct(
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		RepoDomainDb $db,
		PropertyLookup $propertyLookup,
		ItemLookup $itemLookup,
		int $batchSize,
		int $batchSpacingInSeconds,
		string $hostFrom,
		string $hostTo,
		bool $all
	) {
		$this->idPager = $idPager;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->db = $db;
		$this->propertyLookup = $propertyLookup;
		$this->itemLookup = $itemLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
		$this->hostFrom = $hostFrom;
		$this->hostTo = $hostTo;
		$this->entityStore = WikibaseRepo::getEntityStore();
		$this->performer = \User::newSystemUser( \User::MAINTENANCE_SCRIPT_USER, [ 'steal' => true ] );
		$this->loopThroughAll = $all;
	}

	public function rebuild() {
		$ticket = $this->db->getEmptyTransactionTicket( __METHOD__ );
		$db = wfGetDB( DB_REPLICA );

		$counter = 0;
		while ( true ) {

			$entityIds = [];

			if ( $this->loopThroughAll ) {
				$entityIds = $this->idPager->fetchIds( $this->batchSize );
			} else {
				$result = $db->query(
					' SELECT distinct(page_title) from' . $db->tableName( 'page' ) . ' as p '
					. ' inner join ' . $db->tableName( 'pagelinks' ) . ' as pl on p.page_id = pl.pl_from'
					. ' inner join ' .$db->tableName( 'wb_property_info' ). ' as pi on pl.pl_title = CONCAT(\'P\', pi.pi_property_id)'
					. ' where ( p.page_title like \'Q%\' OR p.page_title like \'P%\' ) and pi.pi_info = \'{"type":"quantity"}\''
					. ' LIMIT ' . $this->batchSize . ' OFFSET ' . $counter,
					__METHOD__
				);

				while( $result->next() ) {
					if( str_starts_with($result->current()->page_title, "Q")) {
						$entityIds[] = new ItemId( $result->current()->page_title );
					} else {
						$entityIds[] = new NumericPropertyId( $result->current()->page_title );
					}
				}
			}

			$numEntities = count($entityIds);

			if ( !$entityIds ) {
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

	private function rebuildEntityQuantityForUnit(array $propertyIds ) {
		foreach ( $propertyIds as $entityId ) {
			if ($entityId instanceof NumericPropertyId) {
				$this->updateQuantityUnit(
					$this->propertyLookup->getPropertyForId( $entityId )
				);
			} else if ($entityId instanceof ItemId) {
				$this->updateQuantityUnit(
					$this->itemLookup->getItemForId( $entityId )
				);
			}

		}
	}

	private function updateQuantityUnit( $entity ) {

		$updateCounter = 0;

		/** @var StatementList $statements */
		$statements = $entity->getStatements();
		foreach ( $statements->toArray() as $statement ) {

			$mainSnak = $statement->getMainSnak();

			if ($mainSnak instanceof PropertyNoValueSnak) {
				continue;
			}

			$value = $mainSnak->getDataValue()->getValue();
			if( $value instanceof UnboundedQuantityValue ) {

				$unit = $value->getUnit();

				if ( str_contains( $unit, $this->hostFrom) ) {
					$newUnit = str_replace($this->hostFrom, $this->hostTo, $unit);
					$value = UnboundedQuantityValue::newFromNumber($value->getAmount(), $newUnit);
					$mainSnak = new PropertyValueSnak($mainSnak->getPropertyId(), $value);
					$updateCounter++;
				}
			}

			$statement->setMainSnak($mainSnak);

		}

		if( $updateCounter > 0) {
			$rev = $this->entityStore->saveEntity($entity, "Updating quantity unit", $this->performer, EDIT_UPDATE);
			$this->progressReporter->reportMessage("Updating {$entity->getId()}: revision: {$rev->getRevisionId()} updates: {$updateCounter}");


		}
	}

}
