<?php

namespace Wikibase\Repo\Store;

use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
class TermStoreRebuilder {

	private $propertyTermStore;
	private $itemTermStore;
	private $idPager;
	private $progressReporter;
	private $errorReporter;
	private $loadBalancerFactory;
	private $entityLookup;
	private $batchSize;
	private $batchSpacingInSeconds;

	public function __construct(
		PropertyTermStore $propertyTermStore,
		ItemTermStore $itemTermStore,
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		LBFactory $loadBalancerFactory,
		EntityLookup $entityLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
		$this->propertyTermStore = $propertyTermStore;
		$this->itemTermStore = $itemTermStore;
		$this->idPager = $idPager;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->entityLookup = $entityLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
	}

	public function rebuild() {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );

		while ( true ) {
			$entityIds = $this->idPager->fetchIds( $this->batchSize );

			if ( !$entityIds ) {
				break;
			}

			$this->rebuildTermsForBatch( $entityIds );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->progressReporter->reportMessage(
				'Processed up to entity '
				. $this->idPager->getPosition() . ' (' . end( $entityIds ) . ')'
			);

			if ( $this->batchSpacingInSeconds > 0 ) {
				sleep( $this->batchSpacingInSeconds );
			}
		}
	}

	private function rebuildTermsForBatch( array $entityIds ) {
		foreach ( $entityIds as $entityId ) {
			$this->saveTerms(
				$this->entityLookup->getEntity( $entityId )
			);
		}
	}

	private function saveTerms( EntityDocument $entity ) {
		try {
			if ( $entity instanceof Property ) {
				$this->propertyTermStore->storeTerms( $entity->getId(), $entity->getFingerprint() );
			}

			if ( $entity instanceof Item ) {
				$this->itemTermStore->storeTerms( $entity->getId(), $entity->getFingerprint() );
			}
		} catch ( TermStoreException $ex ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				'Failed to save terms of entity: ' . $entity->getId()->getSerialization()
			);
		}
	}

}
