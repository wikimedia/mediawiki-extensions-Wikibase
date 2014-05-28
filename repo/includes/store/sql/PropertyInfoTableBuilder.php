<?php

namespace Wikibase;

use DatabaseBase;
use MessageReporter;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\EntityLookup;

/**
 * Utility class for rebuilding the wb_property_info table.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilder {

	private $propertyInfoTable;
	private $entityLookup;

	/**
	 * @var MessageReporter $reporter
	 */
	private $reporter;

	/**
	 * @var bool
	 */
	private $useTransactions = true;

	/**
	 * Whether all entries should be updated, or only missing entries
	 *
	 * @var bool
	 */
	private $all = false;

	/**
	 * Starting point
	 *
	 * @var int
	 */
	private $fromId = 1;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	protected $batchSize = 100;

	public function __construct( PropertyInfoTable $propertyInfoTable, EntityLookup $entityLookup ) {
		$this->propertyInfoTable = $propertyInfoTable;
		$this->entityLookup = $entityLookup;
	}

	/**
	 * @return boolean
	 */
	public function getRebuildAll() {
		return $this->all;
	}

	/**
	 * @return int
	 */
	public function getBatchSize() {
		return $this->batchSize;
	}

	/**
	 * @return int
	 */
	public function getFromId() {
		return $this->fromId;
	}

	/**
	 * @param boolean $all
	 */
	public function setRebuildAll( $all ) {
		$this->all = $all;
	}

	/**
	 * @param int $batchSize
	 */
	public function setBatchSize( $batchSize ) {
		$this->batchSize = $batchSize;
	}

	/**
	 * @param int $fromId
	 */
	public function setFromId( $fromId ) {
		$this->fromId = $fromId;
	}

	/**
	 * Sets the reporter to use for reporting progress.
	 *
	 * @param MessageReporter $reporter
	 */
	public function setReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Enables or disables transactions.
	 * The only good reason to disable transactions is to be able to
	 * run the rebuild inside an already existing transaction.
	 *
	 * @param bool $useTransactions
	 */
	public function setUseTransactions( $useTransactions ) {
		$this->useTransactions = $useTransactions;
	}

	/**
	 * Rebuild the property info entries.
	 * Use the rebuildPropertyInfo.php maintenance script to invoke this from the command line.
	 *
	 * Database updates a batched into multiple transactions. Do not call this
	 * method within an (explicit) database transaction.
	 *
	 * @since 0.4
	 */
	public function rebuildPropertyInfo() {
		$dbw = $this->propertyInfoTable->getWriteConnection();

		$rowId = $this->fromId -1;

		$total = 0;

		$join = array();
		$tables = array( 'wb_entity_per_page' );

		if ( !$this->all ) {
			// Find properties in wb_entity_per_page with no corresponding
			// entry in wb_property_info.

			$piTable = $this->propertyInfoTable->getTableName();

			$tables[] = $piTable;
			$join[$piTable] = array( 'LEFT JOIN',
				array(
					'pi_property_id = epp_entity_id',
				)
			);
		}

		while ( true ) {
			// Make sure we are not running too far ahead of the slaves,
			// as that would cause the site to be rendered read only.
			$this->waitForSlaves( $dbw );

			if ( $this->useTransactions ) {
				$dbw->begin();
			}

			$props = $dbw->select(
				$tables,
				array(
					'epp_entity_id',
				),
				array(
					'epp_entity_type = ' . $dbw->addQuotes( Property::ENTITY_TYPE ),
					'epp_entity_id > ' . (int) $rowId,
					$this->all ? '1' : 'pi_property_id IS NULL', // if not $all, only add missing entries
				),
				__METHOD__,
				array(
					'LIMIT' => $this->batchSize,
					// XXX: We currently have a unique key defined as `wb_epp_entity` (`epp_entity_id`,`epp_entity_type`).
					//      This SHOULD be the other way around:  `wb_epp_entity` (`epp_entity_type`, `epp_entity_id`).
					//      Once this is fixed, the below should probable be changed to:
					//      'ORDER BY' => 'epp_entity_type ASC, epp_entity_id ASC'
					'ORDER BY' => 'epp_entity_id ASC',
					'FOR UPDATE'
				),
				$join
			);

			$c = 0;

			foreach ( $props as $row ) {
				$id = PropertyId::newFromNumber( (int)$row->epp_entity_id );
				$this->updatePropertyInfo( $dbw, $id );

				$rowId = $row->epp_entity_id;
				$c+= 1;
			}

			if ( $this->useTransactions ) {
				$dbw->commit();
			}

			$this->report( "Updated $c properties, up to ID $rowId." );
			$total += $c;

			if ( $c < $this->batchSize ) {
				// we are done.
				break;
			}
		}

		return $total;
	}

	/**
	 * Wait for slaves (quietly)
	 *
	 * @todo: this should be in the Database class.
	 * @todo: thresholds should be configurable
	 *
	 * @author Tim Starling (stolen from recompressTracked.php)
	 */
	protected function waitForSlaves() {
		$lb = wfGetLB(); //TODO: allow foreign DB, get from $this->propertyInfoTable

		while ( true ) {
			list( $host, $maxLag ) = $lb->getMaxLag();
			if ( $maxLag < 2 ) {
				break;
			}

			$this->report( "Slaves are lagged by $maxLag seconds, sleeping..." );
			sleep( 5 );
			$this->report( "Resuming..." );
		}
	}

	/**
	 * Updates the property info entry for the given property.
	 * The property is loaded in full using the EntityLookup
	 * provide to the constructor.
	 *
	 * @see Wikibase\PropertyInfoUpdate
	 * @throws \RuntimeException
	 *
	 * @since 0.4
	 *
	 * @param DatabaseBase $dbw the database connection to use
	 * @param PropertyId $id the Property to process
	 */
	protected function updatePropertyInfo( DatabaseBase $dbw, PropertyId $id ) {
		$property = $this->entityLookup->getEntity( $id );

		if( !$property instanceof Property ) {
			throw new \RuntimeException(
				"EntityLookup didn't return a Property for id " . $id->getPrefixedId()
			);
		}

		//FIXME: Needs to be in sync with what PropertyHandler::getEntityModificationUpdates does!
		$info = array(
			PropertyInfoStore::KEY_DATA_TYPE => $property->getDataTypeId()
		);

		$this->propertyInfoTable->setPropertyInfo(
			$property->getId(),
			$info
		);
	}

	/**
	 * reports a message
	 *
	 * @since 0.4
	 *
	 * @param $msg
	 */
	protected function report( $msg ) {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}
