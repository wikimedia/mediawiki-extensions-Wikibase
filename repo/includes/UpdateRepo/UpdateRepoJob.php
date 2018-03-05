<?php

namespace Wikibase\Repo\UpdateRepo;

use InvalidArgumentException;
use Job;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntityFactory;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Lib\FormatableSummary;
use Wikibase\SummaryFormatter;

/**
 * Job template for updating the repo after a change in client.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepoJob extends Job {

	/**
	 * @var EntityRevisionLookup
	 */
	protected $entityRevisionLookup;

	/**
	 * @var EntityStore
	 */
	protected $entityStore;

	/**
	 * @var SummaryFormatter
	 */
	protected $summaryFormatter;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @see Job::__construct
	 *
	 * @param string $command
	 * @param Title $title
	 * @param array|bool $params
	 */
	public function __construct( $command, Title $title, $params = false ) {
		parent::__construct( $command, $title, $params );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initRepoJobServices(
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->newEditEntityFactory()
		);
	}

	protected function initRepoJobServices(
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		EditEntityFactory $editEntityFactory
	) {
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->editEntityFactory = $editEntityFactory;
	}

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

		try {
			$itemId = new ItemId( $params['entityId'] );
		} catch ( InvalidArgumentException $ex ) {
			wfDebugLog(
				'UpdateRepo',
				__FUNCTION__ . ": Invalid ItemId serialization " . $params['entityId'] . " given."
			);

			return null;
		}

		try {
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $itemId, 0, EntityRevisionLookup::LATEST_FROM_MASTER );
		} catch ( StorageException $ex ) {
			wfDebugLog(
				'UpdateRepo',
				__FUNCTION__ . ": EntityRevision couldn't be loaded for " . $itemId->getSerialization() . ": " . $ex->getMessage()
			);

			return null;
		}

		if ( $entityRevision ) {
			return $entityRevision->getEntity();
		}

		wfDebugLog(
			'UpdateRepo',
			__FUNCTION__ . ": EntityRevision not found for " . $itemId->getSerialization()
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
			wfDebugLog( 'UpdateRepo', __FUNCTION__ . ': attemptSave for '
				. $itemId->getSerialization() . ' failed: ' . $status->getMessage()->text() );
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
		if ( !$user || !$user->isLoggedIn() ) {
			wfDebugLog( 'UpdateRepo', "User $name doesn't exist." );
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
