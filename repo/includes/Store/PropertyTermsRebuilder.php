<?php

namespace Wikibase\Repo\Store;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\TermStore\PropertyTermStore;
use Wikimedia\Rdbms\ILBFactory;

/**
 * @license GPL-2.0-or-later
 */
class PropertyTermsRebuilder {

	private $propertyTermStore;
	private $idPager;
	private $progressReporter;
	private $errorReporter;
	private $loadBalancerFactory;
	private $propertyLookup;
	private $batchSize;
	private $batchSpacingInSeconds;

	public function __construct(
		PropertyTermStore $propertyTermStore,
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		ILBFactory $loadBalancerFactory,
		PropertyLookup $propertyLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
		$this->propertyTermStore = $propertyTermStore;
		$this->idPager = $idPager;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->propertyLookup = $propertyLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
	}

	public function rebuild() {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );

		while ( true ) {
			$propertyIds = $this->idPager->fetchIds( $this->batchSize );

			if ( !$propertyIds ) {
				break;
			}

			$this->rebuildTermsForBatch( $propertyIds );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->progressReporter->reportMessage(
				'Processed up to page '
				. $this->idPager->getPosition() . ' (' . end( $propertyIds ) . ')'
			);

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
			$this->propertyTermStore->storeTerms( $property->getId(), $property->getFingerprint() );
			$this->loadBalancerFactory->commitMasterChanges( __METHOD__ );
		} catch ( Exception $ex ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				'Failed to save terms of property: ' . $property->getId()->getSerialization()
			);
		}
	}

}
