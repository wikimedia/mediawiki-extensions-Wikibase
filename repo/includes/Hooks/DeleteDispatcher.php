<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use TitleFactory;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\ChangeModification\DispatchChangeDeletionNotificationJob;

/**
 * Hook for dispatching DeleteDispatchNotificationJob on repo which in turn will fetch archived revisions
 * and dispatch deletion jobs on the clients.
 *
 * @license GPL-2.0-or-later
 */
class DeleteDispatcher implements PageDeleteCompleteHook {

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/** @var EntityIdLookup */
	private $entityIdLookup;

	/** @var array */
	private $localClientDatabases;

	/** @var TitleFactory */
	private $titleFactory;

	/**
	 * @param JobQueueGroup $jobQueueGroup
	 * @param TitleFactory $titleFactory
	 * @param EntityIdLookup $entityIdLookup
	 * @param array $localClientDatabases
	 */
	public function __construct(
		JobQueueGroup $jobQueueGroup,
		TitleFactory $titleFactory,
		EntityIdLookup $entityIdLookup,
		array $localClientDatabases
	) {
		$this->jobQueueGroup = $jobQueueGroup;
		$this->titleFactory = $titleFactory;
		$this->entityIdLookup = $entityIdLookup;
		$this->localClientDatabases = $localClientDatabases;
	}

	public static function factory(
		JobQueueGroup $jobQueueGroup,
		TitleFactory $titleFactory,
		EntityIdLookup $entityIdLookup,
		SettingsArray $repoSettings
	): self {
		return new self(
			$jobQueueGroup,
			$titleFactory,
			$entityIdLookup,
			$repoSettings->getSetting( 'localClientDatabases' )
		);
	}

	/** @inheritDoc */
	public function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		if ( $archivedRevisionCount === 0 || !$this->localClientDatabases ) {
			return true;
		}

		$title = $this->titleFactory->newFromPageIdentity( $page );

		// Abort if not entityId
		$entityId = $this->entityIdLookup->getEntityIdForTitle( $title );
		if ( $entityId === null ) {
			return true;
		}

		$jobParams = [
			"pageId" => $pageID,
			"archivedRevisionCount" => $archivedRevisionCount,
		];
		$job = new DispatchChangeDeletionNotificationJob( $title, $jobParams );

		$this->jobQueueGroup->push( $job );
	}
}
