<?php

namespace Wikibase\Repo\UpdateRepo;

use Job;
use Psr\Log\LoggerInterface;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookupException;
use Wikibase\Lib\FormatableSummary;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\SummaryFormatter;

/**
 * Job template for updating the repo after a change in client.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepoJob extends Job {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * @var MediawikiEditEntityFactory
	 */
	private $editEntityFactory;

	protected function initRepoJobServices(
		EntityLookup $entityLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		LoggerInterface $logger,
		MediawikiEditEntityFactory $editEntityFactory
	) {
		$this->entityLookup = $entityLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->logger = $logger;
		$this->editEntityFactory = $editEntityFactory;
	}

	/**
	 * Initialize repo services from global state.
	 */
	abstract protected function initRepoJobServicesFromGlobalState();

	/**
	 * Get a Summary object for the edit
	 *
	 * @return FormatableSummary
	 */
	abstract public function getSummary();

	/**
	 * Whether the propagated update is valid (and thus should be applied)
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	abstract protected function verifyValid( Item $item );

	/**
	 * Apply the changes needed to the given Item.
	 *
	 * @param Item $item
	 *
	 * @return bool
	 */
	abstract protected function applyChanges( Item $item );

	/**
	 * @return Item|null
	 */
	private function getItem() {
		$params = $this->getParams();
		$itemId = new ItemId( $params['entityId'] );
		try {
			$entity = $this->entityLookup->getEntity( $itemId );
		} catch ( EntityLookupException $ex ) {
			$this->logger->debug(
				'{method}: EntityRevision couldn\'t be loaded for {itemIdSerialization}: {msg}',
				[
					'method' => __METHOD__,
					'itemIdSerialization' => $itemId->getSerialization(),
					'msg' => $ex->getMessage(),
				]
			);

			return null;
		}

		if ( $entity instanceof Item ) {
			return $entity;
		}

		$this->logger->debug(
			'{method}: EntityRevision not found for {itemIdSerialization}',
			[
				'method' => __METHOD__,
				'itemIdSerialization' => $itemId->getSerialization(),
			]
		);

		return null;
	}

	/**
	 * Save the new version of the given item.
	 *
	 * @param Item $item
	 * @param User $user
	 *
	 * @return bool
	 */
	private function saveChanges( Item $item, User $user ) {
		$summary = $this->getSummary();
		$itemId = $item->getId();

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$editEntity = $this->editEntityFactory->newEditEntity( $user, $item->getId(), 0, true );
		$status = $editEntity->attemptSave(
			$item,
			$summaryString,
			EDIT_UPDATE,
			false,
			// Don't (un)watch any pages here, as the user didn't explicitly kick this off
			$this->entityStore->isWatching( $user, $itemId )
		);

		if ( !$status->isOK() ) {
			$this->logger->debug(
				'{method}: attemptSave for {itemIdSerialization} failed: {msgText}',
				[
					'method' => __METHOD__,
					'itemIdSerialization' => $itemId->getSerialization(),
					'msgText' => $status->getMessage()->text(),
				]
			);
		}

		return $status->isOK();
	}

	/**
	 * @param string $name
	 *
	 * @return User|bool
	 */
	private function getUser( $name ) {
		$user = User::newFromName( $name );
		if ( !$user || !$user->isRegistered() ) {
			$this->logger->debug( 'User {name} doesn\'t exist.', [ 'name' => $name ] );
			return false;
		}

		return $user;
	}

	/**
	 * @return bool success
	 */
	public function run() {
		$params = $this->getParams();

		$user = $this->getUser( $params['user'] );
		if ( !$user ) {
			return true;
		}

		$item = $this->getItem();
		if ( $item && $this->verifyValid( $item ) ) {
			$this->applyChanges( $item );
			$this->saveChanges( $item, $user );
		}

		return true;
	}

}
