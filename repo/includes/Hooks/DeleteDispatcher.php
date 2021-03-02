<?php

declare( strict_types = 1 );
namespace Wikibase\Repo\Hooks;

use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use Wikibase\Lib\SettingsArray;
use Wikibase\Repo\ChangeModification\DispatchChangeDeletionNotificationJob;
use Wikibase\Repo\Content\EntityContentFactory;

/**
 * Hook for dispatching DeleteDispatchNotificationJob on repo which in turn will fetch archived revisions
 * and dispatch deletion jobs on the clients.
 *
 * @license GPL-2.0-or-later
 */
class DeleteDispatcher implements ArticleDeleteCompleteHook {

	/** @var callable */
	private $jobQueueGroupFactory;

	/** @var EntityContentFactory */
	private $entityContentFactory;

	/** @var array */
	private $localClientDatabases;

	/**
	 * @param callable $jobQueueGroupFactory
	 * @param EntityContentFactory $entityContentFactory
	 * @param array $localClientDatabases
	 */
	public function __construct(
		callable $jobQueueGroupFactory,
		EntityContentFactory $entityContentFactory,
		array $localClientDatabases
	) {
		$this->jobQueueGroupFactory = $jobQueueGroupFactory;
		$this->entityContentFactory = $entityContentFactory;
		$this->localClientDatabases = $localClientDatabases;
	}

	public static function factory(
		EntityContentFactory $entityContentFactory,
		SettingsArray $repoSettings
	): self {
		return new self(
			'JobQueueGroup::singleton',
			$entityContentFactory,
			$repoSettings->getSetting( 'localClientDatabases' )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function onArticleDeleteComplete( $wikiPage, $user, $reason, $id, $content, $logEntry, $archivedRevisionCount ) {
		if ( $archivedRevisionCount === 0 || empty( $this->localClientDatabases ) ) {
			return true;
		}

		$title = $wikiPage->getTitle();
		if ( $title === null ) {
			return true;
		}

		// Abort if not entityId
		$entityId = $this->entityContentFactory->getEntityIdForTitle( $title );
		if ( $entityId === null ) {
			return true;
		}

		$jobParams = [
			"pageId" => $id,
			"archivedRevisionCount" => $archivedRevisionCount
		];
		$job = new DispatchChangeDeletionNotificationJob( $title, $jobParams );

		call_user_func( $this->jobQueueGroupFactory, false )->push( $job );
	}

}
