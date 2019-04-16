<?php

namespace Wikibase\Repo\Store;

use Exception;
use Onoi\MessageReporter\MessageReporter;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Services\EntityId\SeekableEntityIdPager;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\TermStore\ItemTermStore;
use Wikibase\TermStore\ItemTermStore;
use Wikimedia\Rdbms\LBFactory;

/**
 * @license GPL-2.0-or-later
 */
class ItemTermsRebuilder {

	private $itemTermStore;
	private $idPager;
	private $progressReporter;
	private $errorReporter;
	private $loadBalancerFactory;
	private $entityLookup;
	private $batchSize;
	private $batchSpacingInSeconds;

	/**
	 * @param ItemTermStore $itemTermStore,
	 * @param SeekableEntityIdPager $idPager,
	 * @param MessageReporter $progressReporter,
	 * @param MessageReporter $errorReporter,
	 * @param LBFactory $loadBalancerFactory,
	 * @param EntityLookup $entityLookup,
	 * @param int $batchSize,
	 * @param int $batchSpacingInSeconds
	 */
	public function __construct(
		ItemTermStore $itemTermStore,
		SeekableEntityIdPager $idPager,
		MessageReporter $progressReporter,
		MessageReporter $errorReporter,
		LBFactory $loadBalancerFactory,
		EntityLookup $entityLookup,
		$batchSize,
		$batchSpacingInSeconds
	) {
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
			$itemIds = $this->idPager->fetchIds( $this->batchSize );

			if ( !$itemIds ) {
				break;
			}

			$this->rebuildTermsForBatch( $itemIds );

			$this->loadBalancerFactory->commitAndWaitForReplication( __METHOD__, $ticket );

			$this->progressReporter->reportMessage(
				'Processed up to page '
				. $this->idPager->getPosition() . ' (' . end( $itemIds ) . ')'
			);

			if ( $this->batchSpacingInSeconds > 0 ) {
				sleep( $this->batchSpacingInSeconds );
			}
		}
	}

	private function rebuildTermsForBatch( array $itemIds ) {
		foreach ( $itemIds as $itemId ) {
			$this->saveTerms(
				$this->entityLookup->getEntity( $itemId )
			);
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
