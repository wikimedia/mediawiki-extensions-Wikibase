<?php

namespace Wikibase;

use RuntimeException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\Reporting\MessageReporter;

/**
 * Utility class for rebuilding the wb_property_info table.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilder {

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var PropertyInfoBuilder
	 */
	private $propertyInfoBuilder;

	/**
	 * @var MessageReporter|null
	 */
	private $reporter = null;

	/**
	 * @var bool
	 */
	private $useTransactions = true;

	/**
	 * Whether all entries should be updated, or only missing entries
	 *
	 * @var bool
	 */
	private $shouldUpdateAllEntities = false;

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
	private $batchSize = 100;

	/**
	 * @param PropertyInfoTable $propertyInfoTable
	 * @param EntityLookup $entityLookup
	 * @param PropertyInfoBuilder $propertyInfoBuilder
	 */
	public function __construct(
		PropertyInfoTable $propertyInfoTable,
		EntityLookup $entityLookup,
		PropertyInfoBuilder $propertyInfoBuilder
	) {
		$this->propertyInfoTable = $propertyInfoTable;
		$this->entityLookup = $entityLookup;
		$this->propertyInfoBuilder = $propertyInfoBuilder;
	}

	/**
	 * @return bool
	 */
	public function getRebuildAll() {
		return $this->shouldUpdateAllEntities;
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
	 * @param bool $all
	 */
	public function setRebuildAll( $all ) {
		$this->shouldUpdateAllEntities = $all;
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

		$rowId = $this->fromId - 1;

		$total = 0;

		$join = array();
		$tables = array( 'wb_entity_per_page' );

		if ( !$this->shouldUpdateAllEntities ) {
			// Find properties in wb_entity_per_page with no corresponding
			// entry in wb_property_info.

			$piTable = $this->propertyInfoTable->getTableName();

			$tables[] = $piTable;
			$join[$piTable] = array( 'LEFT JOIN',
				array(
					// FIXME: This makes assumptions about the structure of serialized entity ids
					'pi_property_id = SUBSTRING(epp_entity_id FROM 2)',
				)
			);
		}

		while ( true ) {
			// Make sure we are not running too far ahead of the slaves,
			// as that would cause the site to be rendered read only.
			wfWaitForSlaves();

			if ( $this->useTransactions ) {
				$dbw->begin( __METHOD__ );
			}

			//FIXME: use an EntityIdPager from EntityPerPage
			$props = $dbw->select(
				$tables,
				array(
					'epp_entity_id',
				),
				array(
					'epp_entity_type = ' . $dbw->addQuotes( Property::ENTITY_TYPE ),
					'SUBSTRING(epp_entity_id FROM 2) > ' . $rowId,
					'epp_redirect_target IS NULL',
					$this->shouldUpdateAllEntities ? '1' : 'pi_property_id IS NULL', // if not $all, only add missing entries
				),
				__METHOD__,
				array(
					'LIMIT' => $this->batchSize,
					'ORDER BY' => 'epp_entity_id ASC',
					'FOR UPDATE'
				),
				$join
			);

			$c = 0;

			foreach ( $props as $row ) {
				$id = new PropertyId( $row->epp_entity_id );
				$this->updatePropertyInfo( $id );

				$rowId = (int) substr( $row->epp_entity_id, 1 );
				$c++;
			}

			if ( $this->useTransactions ) {
				$dbw->commit( __METHOD__ );
			}

			$this->reportMessage( "Updated $c properties, up to ID $rowId." );
			$total += $c;

			if ( $c < $this->batchSize ) {
				// we are done.
				break;
			}
		}

		return $total;
	}

	/**
	 * Updates the property info entry for the given property.
	 * The property is loaded in full using the EntityLookup
	 * provide to the constructor.
	 *
	 * @throws RuntimeException
	 *
	 * @param PropertyId $id the Property to process
	 */
	private function updatePropertyInfo( PropertyId $id ) {
		$property = $this->entityLookup->getEntity( $id );

		if ( !( $property instanceof Property ) ) {
			throw new RuntimeException(
				'EntityLookup did not return a Property for id ' . $id->getSerialization()
			);
		}

		$info = $this->propertyInfoBuilder->buildPropertyInfo( $property );

		$this->propertyInfoTable->setPropertyInfo(
			$id,
			$info
		);
	}

	/**
	 * @param string $msg
	 */
	private function reportMessage( $msg ) {
		if ( $this->reporter ) {
			$this->reporter->reportMessage( $msg );
		}
	}

}
