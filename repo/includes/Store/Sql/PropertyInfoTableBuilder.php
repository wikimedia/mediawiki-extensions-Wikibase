<?php

namespace Wikibase\Repo\Store\Sql;

use Onoi\MessageReporter\MessageReporter;
use RuntimeException;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\Sql\PropertyInfoTable;
use Wikibase\Repo\PropertyInfoBuilder;

/**
 * Utility class for rebuilding the wb_property_info table.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class PropertyInfoTableBuilder {

	/**
	 * @var PropertyInfoTable
	 */
	private $propertyInfoTable;

	/**
	 * @var PropertyLookup
	 */
	private $propertyLookup;

	/**
	 * @var PropertyInfoBuilder
	 */
	private $propertyInfoBuilder;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var MessageReporter|null
	 */
	private $reporter = null;

	/**
	 * Whether all entries should be updated, or only missing entries
	 *
	 * @var bool
	 */
	private $shouldUpdateAllEntities = false;

	/**
	 * The batch size, giving the number of rows to be updated in each database transaction.
	 *
	 * @var int
	 */
	private $batchSize = 100;

	public function __construct(
		PropertyInfoTable $propertyInfoTable,
		PropertyLookup $propertyLookup,
		PropertyInfoBuilder $propertyInfoBuilder,
		EntityNamespaceLookup $entityNamespaceLookup
	) {
		$this->propertyInfoTable = $propertyInfoTable;
		$this->propertyLookup = $propertyLookup;
		$this->propertyInfoBuilder = $propertyInfoBuilder;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
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
	 * Sets the reporter to use for reporting progress.
	 *
	 * @param MessageReporter $reporter
	 */
	public function setReporter( MessageReporter $reporter ) {
		$this->reporter = $reporter;
	}

	/**
	 * Rebuild the property info entries.
	 * Use the rebuildPropertyInfo.php maintenance script to invoke this from the command line.
	 *
	 * Database updates a batched into multiple transactions. Do not call this
	 * method within an (explicit) database transaction.
	 */
	public function rebuildPropertyInfo() {
		$propertyNamespace = $this->entityNamespaceLookup->getEntityNamespace(
			Property::ENTITY_TYPE
		);
		if ( $propertyNamespace === null ) {
			throw new RuntimeException( __METHOD__ . ' can not run with no Property namespace defined.' );
		}

		$dbw = $this->propertyInfoTable->getDomainDb()->connections()->getWriteConnection();

		$total = 0;

		$queryBuilderTemplate = $dbw->newSelectQueryBuilder();
		$queryBuilderTemplate->select( [ 'page_title', 'page_id' ] )
			->from( 'page' );

		if ( !$this->shouldUpdateAllEntities ) {
			$queryBuilderTemplate->leftJoin(
				$this->propertyInfoTable->getTableName(),
				null,
				$dbw->buildConcat( [ "'P'", 'pi_property_id' ] ) . ' = page_title'
			);
			$queryBuilderTemplate->where( 'pi_property_id IS NULL' ); // only add missing entries
		}

		$queryBuilderTemplate->where( [ 'page_namespace' => $propertyNamespace ] )
			->orderBy( 'page_id', $queryBuilderTemplate::SORT_ASC )
			->limit( $this->batchSize )
			->forUpdate()
			->caller( __METHOD__ );

		$ticket = $this->propertyInfoTable->getDomainDb()->getEmptyTransactionTicket( __METHOD__ );
		$pageId = 1;

		while ( true ) {
			// Make sure we are not running too far ahead of the replicas,
			// as that would cause the site to be rendered read only.
			$this->propertyInfoTable->getDomainDb()->commitAndWaitForReplication( __METHOD__, $ticket );

			$dbw->startAtomic( __METHOD__ );

			$queryBuilder = clone $queryBuilderTemplate;
			$queryBuilder->where( 'page_id > ' . $pageId );
			$props = $queryBuilder->fetchResultSet();

			$c = 0;

			foreach ( $props as $row ) {
				$this->updatePropertyInfo( new NumericPropertyId( $row->page_title ) );
				$pageId = (int)$row->page_id;
				$c++;
			}

			$dbw->endAtomic( __METHOD__ );

			$this->reportMessage( "Updated $c properties, up to page ID $pageId." );
			$total += $c;

			if ( $c < $this->batchSize ) {
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
	 * @param NumericPropertyId $id the Property to process
	 */
	private function updatePropertyInfo( NumericPropertyId $id ) {
		$property = $this->propertyLookup->getPropertyForId( $id );

		if ( $property === null ) {
			throw new RuntimeException(
				'Did not find Property with id ' . $id->getSerialization()
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
