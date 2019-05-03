<?php

namespace Wikibase\Repo\Store;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\TermStore\PropertyTermStore;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
class PropertyTermsRebuilder {

	private $propertyTermStore;
	private $propertyIds;
	private $progressReporter;
	private $errorReporter;
	private $loadBalancerFactory;
	private $propertyLookup;
	private $batchSize;
	private $batchSpacingInSeconds;

	public function __construct(
		PropertyTermStore $propertyTermStore,
		\Iterator $propertyIds,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		LBFactory $loadBalancerFactory,
		PropertyLookup $propertyLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
		$this->propertyTermStore = $propertyTermStore;
		$this->propertyIds = $propertyIds;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->propertyLookup = $propertyLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
	}

	public function rebuild() {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );

		foreach ( $this->getIdBatches() as $propertyIds ) {
			$this->rebuildTermsForBatch( $propertyIds );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->progressReporter->reportMessage(
				'Processed up to id ' . end( $propertyIds )
			);

			if ( $this->batchSpacingInSeconds > 0 ) {
				sleep( $this->batchSpacingInSeconds );
			}
		}
	}

	private function getIdBatches() {
		$idsInBatch = [];

		foreach ( $this->propertyIds as $propertyId ) {
			$idsInBatch[] = $propertyId;

			if ( count( $idsInBatch ) >= $this->batchSize ) {
				yield $idsInBatch;
				$idsInBatch = [];
			}
		}

		if ( $idsInBatch !== [] ) {
			yield $idsInBatch;
		}
	}

	private function rebuildTermsForBatch( $propertyIds ) {
		foreach ( $propertyIds as $propertyId ) {
			// TODO: catch errors
			$property = $this->propertyLookup->getPropertyForId( $propertyId );

			if ( $property !== null ) {
				$this->saveTerms( $property );
			}
		}
	}

	private function saveTerms( Property $property ) {
		try {
			$this->propertyTermStore->storeTerms( $property->getId(), $property->getFingerprint() );
		} catch ( Exception $ex ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				'Failed to save terms of property: ' . $property->getId()->getSerialization()
			);
		}
	}

}
