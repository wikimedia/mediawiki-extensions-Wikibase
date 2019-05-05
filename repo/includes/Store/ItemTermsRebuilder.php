<?php

namespace Wikibase\Repo\Store;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
class ItemTermsRebuilder {

	private $itemTermStore;
	private $itemIds;
	private $progressReporter;
	private $errorReporter;
	private $loadBalancerFactory;
	private $itemLookup;
	private $batchSize;
	private $batchSpacingInSeconds;

	public function __construct(
		ItemTermStore $itemTermStore,
		$itemIdIterable,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		LBFactory $loadBalancerFactory,
		ItemLookup $itemLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
		$this->itemTermStore = $itemTermStore;
		$this->itemIds = $itemIdIterable;
		$this->progressReporter = $progressReporter;
		$this->errorReporter = $errorReporter;
		$this->loadBalancerFactory = $loadBalancerFactory;
		$this->itemLookup = $itemLookup;
		$this->batchSize = $batchSize;
		$this->batchSpacingInSeconds = $batchSpacingInSeconds;
	}

	public function rebuild() {
		$ticket = $this->loadBalancerFactory->getEmptyTransactionTicket( __METHOD__ );

		foreach ( $this->getIdBatches() as $itemIds ) {
			$this->progressReporter->reportMessage(
				'Rebuilding ' . $itemIds[0] . ' till ' . end( $itemIds )
			);

			$this->rebuildTermsForBatch( $itemIds );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			if ( $this->batchSpacingInSeconds > 0 ) {
				sleep( $this->batchSpacingInSeconds );
			}
		}
	}

	private function getIdBatches() {
		$idsInBatch = [];

		foreach ( $this->itemIds as $itemId ) {
			$idsInBatch[] = $itemId;

			if ( count( $idsInBatch ) >= $this->batchSize ) {
				yield $idsInBatch;
				$idsInBatch = [];
			}
		}

		if ( $idsInBatch !== [] ) {
			yield $idsInBatch;
		}
	}

	private function rebuildTermsForBatch( array $itemIds ) {
		foreach ( $itemIds as $itemId ) {
			// TODO: catch errors
			$item = $this->itemLookup->getItemForId( $itemId );

			if ( $item !== null ) {
				$this->saveTerms( $item );
			}
		}
	}

	private function saveTerms( Item $item ) {
		try {
			$this->itemTermStore->storeTerms( $item->getId(), $item->getFingerprint() );
		} catch ( Exception $ex ) {
			$this->loadBalancerFactory->rollbackMasterChanges( __METHOD__ );
			$this->errorReporter->reportMessage(
				'Failed to save terms of item: ' . $item->getId()->getSerialization()
			);
		}
	}

}
