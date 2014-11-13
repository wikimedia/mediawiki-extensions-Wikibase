<?php

namespace Wikibase\Repo\UpdateRepo;

use InvalidArgumentException;
use Job;
use Title;
use User;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\EditEntity;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Summary;
use Wikibase\SummaryFormatter;

/**
 * Job template for updating the repo after a change in client.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
abstract class UpdateRepoJob extends Job {

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

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
	 * @var EntityPermissionChecker
	 */
	protected $entityPermissionChecker;

	/**
	 * @param string $command job command
	 * @param Title $title Ignored
	 * @param array $params
	 */
	public function __construct( $name, Title $title, array $params ) {
		parent::__construct( $name, $title, $params );
		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initRepoJobServices(
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->getEntityStore(),
			$wikibaseRepo->getSummaryFormatter(),
			$wikibaseRepo->getEntityPermissionChecker()
		);
	}

	protected function initRepoJobServices(
		EntityTitleLookup $entityTitleLookup,
		EntityRevisionLookup $entityRevisionLookup,
		EntityStore $entityStore,
		SummaryFormatter $summaryFormatter,
		EntityPermissionChecker $entityPermissionChecker
	) {
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->entityStore = $entityStore;
		$this->summaryFormatter = $summaryFormatter;
		$this->entityPermissionChecker = $entityPermissionChecker;
	}

	/**
	 * Get a Summary object for the edit
	 *
	 * @return Summary
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
			$entityRevision = $this->entityRevisionLookup->getEntityRevision( $itemId );
		} catch ( StorageException $ex ) {
			wfDebugLog(
				'UpdateRepo',
				__FUNCTION__ . ": EntityRevision not found for " . $itemId->getSerialization()
			);

			$entityRevision = null;
		}

		if ( $entityRevision ) {
			return $entityRevision->getEntity();
		}

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

		$editEntity = new EditEntity(
			$this->entityTitleLookup,
			$this->entityRevisionLookup,
			$this->entityStore,
			$this->entityPermissionChecker,
			$item,
			$user,
			true
		);

		$summaryString = $this->summaryFormatter->formatSummary( $summary );

		$status = $editEntity->attemptSave(
			$summaryString,
			EDIT_UPDATE,
			false,
			// Don't (un)watch any pages here, as the user didn't explicitly kick this off
			$this->entityStore->isWatching( $user, $item->getid() )
		);

		if ( !$status->isOK() ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": attemptSave failed: " . $status->getMessage()->text() );
		}

		wfProfileOut( __METHOD__ );

		return $status->isOK();
	}

	/**
	 * @return bool
	 */
	private function getUser( $name ) {
		$user = User::newFromName( $name );
		if ( !$user || !$user->isLoggedIn() ) {
			// This should never happen as we check with CentralAuth
			// that the user actually does exist
			wfLogWarning( "User $name doesn't exist while CentralAuth pretends it does" );
			wfProfileOut( __METHOD__ );
			return false;
		}

		return $user;
	}

	/**
	 * Run the job
	 *
	 * @return boolean success
	 */
	public function run() {
		wfProfileIn( __METHOD__ );
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

		wfProfileOut( __METHOD__ );
		return true;
	}

}
