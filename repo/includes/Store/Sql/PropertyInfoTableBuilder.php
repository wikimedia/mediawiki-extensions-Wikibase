<?php

namespace Wikibase;

use MediaWiki\MediaWikiServices;
use RuntimeException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Reporting\MessageReporter;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;

/**
 * Utility class for rebuilding the wb_property_info table.
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
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

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
	 * @param EntityIdComposer $entityIdComposer
	 */
	public function __construct(
		PropertyInfoTable $propertyInfoTable,
		EntityLookup $entityLookup,
		PropertyInfoBuilder $propertyInfoBuilder,
		EntityIdComposer $entityIdComposer
	) {
		$this->propertyInfoTable = $propertyInfoTable;
		$this->entityLookup = $entityLookup;
		$this->propertyInfoBuilder = $propertyInfoBuilder;
		$this->entityIdComposer = $entityIdComposer;
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
					'pi_property_id = epp_entity_id',
				)
			);
		}

		// @TODO: Inject the LBFactory
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$ticket = $lbFactory->getEmptyTransactionTicket( __METHOD__ );
		while ( true ) {
			// Make sure we are not running too far ahead of the slaves,
			// as that would cause the site to be rendered read only.
			$lbFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			if ( $this->useTransactions ) {
				$dbw->startAtomic( __METHOD__ );
			}

			//FIXME: use an EntityIdPager from EntityPerPage
			$props = $dbw->select(
				$tables,
				array(
					'epp_entity_id',
				),
				array(
					'epp_entity_type = ' . $dbw->addQuotes( Property::ENTITY_TYPE ),
					'epp_entity_id > ' . (int) $rowId,
					'epp_redirect_target IS NULL',
					$this->shouldUpdateAllEntities ? '1' : 'pi_property_id IS NULL', // if not $all, only add missing entries
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
				$id = $this->entityIdComposer->composeEntityId(
					'',
					Property::ENTITY_TYPE,
					(int)$row->epp_entity_id
				);
				$this->updatePropertyInfo( $id );

				$rowId = $row->epp_entity_id;
				$c++;
			}

			if ( $this->useTransactions ) {
				$dbw->endAtomic( __METHOD__ );
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
			$property->getId(),
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
