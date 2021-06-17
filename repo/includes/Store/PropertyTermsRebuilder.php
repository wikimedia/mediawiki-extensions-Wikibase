<?php

namespace Wikibase\Repo\Store;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\Lib\Rdbms\RepoDomainDb;

/**
 * @license GPL-2.0-or-later
 */
class PropertyTermsRebuilder {

	/** @var PropertyTermStoreWriter */
	private $propertyTermStoreWriter;
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
	/** @var int */
	private $batchSize;
	/** @var int */
	private $batchSpacingInSeconds;

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
		PropertyTermStoreWriter $propertyTermStoreWriter,
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		RepoDomainDb $db,
		PropertyLookup $propertyLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
		$this->propertyTermStoreWriter = $propertyTermStoreWriter;
		$this->idPager = $idPager;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->db = $db;
		$this->propertyLookup = $propertyLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
	}

	public function rebuild() {
		$ticket = $this->db->getEmptyTransactionTicket( __METHOD__ );

		while ( true ) {
			$propertyIds = $this->idPager->fetchIds( $this->batchSize );

			if ( !$propertyIds ) {
				break;
			}

			$this->rebuildTermsForBatch( $propertyIds );

			$success = $this->db->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->progressReporter->reportMessage(
				'Processed up to page '
				. $this->idPager->getPosition() . ' (' . end( $propertyIds ) . ')'
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

	private function rebuildTermsForBatch( array $propertyIds ) {
		foreach ( $propertyIds as $propertyId ) {
			$this->saveTerms(
				$this->propertyLookup->getPropertyForId( $propertyId )
			);
		}
	}

	private function saveTerms( Property $property ) {
		try {
			$this->propertyTermStoreWriter->storeTerms( $property->getId(), $property->getFingerprint() );
		} catch ( Exception $ex ) {
			$this->errorReporter->reportMessage(
				'Failed to save terms of property: ' . $property->getId()->getSerialization()
			);
			throw $ex;
		}
	}

}
