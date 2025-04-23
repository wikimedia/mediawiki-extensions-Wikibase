<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Hooks;

use Exception;
use MediaWiki\Content\Content;
use MediaWiki\Logging\ManualLogEntry;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageUndeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Repo\Content\EntityContent;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Notifications\ChangeNotifier;

/**
 * File defining the hook handlers for Mediawiki page deletion and undeletion
 *
 * @license GPL-2.0-or-later
 */
class PageDeleteHooksHandler implements
	PageDeleteCompleteHook,
	PageUndeleteCompleteHook
{

	private ChangeNotifier $changeNotifier;
	private EntityContentFactory $entityContentFactory;
	private EntityStoreWatcher $entityStoreWatcher;

	public function __construct(
		ChangeNotifier $changeNotifier,
		EntityContentFactory $entityContentFactory,
		EntityStoreWatcher $entityStoreWatcher
	) {
		$this->changeNotifier = $changeNotifier;
		$this->entityContentFactory = $entityContentFactory;
		$this->entityStoreWatcher = $entityStoreWatcher;
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
		$content = $this->getPageContent( $deletedRev );
		// Bail out if we are not looking at an entity
		if ( !$content || !$this->entityContentFactory->isEntityContentModel( $content->getModel() ) ) {
			return;
		}

		/** @var EntityContent $content */
		'@phan-var EntityContent $content';

		// Notify storage/lookup services that the entity was deleted. Needed to track page-level deletion.
		// May be redundant in some cases. Take care not to cause infinite regress.
		$this->entityStoreWatcher->entityDeleted( $content->getEntityId() );

		$this->changeNotifier->notifyOnPageDeleted( $content, $deleter->getUser(), $logEntry->getTimestamp() );
	}

	private function getPageContent( RevisionRecord $revisionRecord ): ?Content {
		try {
			$content = $revisionRecord->getMainContentRaw();
		} catch ( Exception $ex ) {
			wfLogWarning( __METHOD__ . ': failed to load content during deletion! '
				. $ex->getMessage() );

			$content = null;
		}
		return $content;
	}

	/** @inheritDoc */
	public function onPageUndeleteComplete(
		ProperPageIdentity $page,
		Authority $restorer,
		string $reason,
		RevisionRecord $restoredRev,
		ManualLogEntry $logEntry,
		int $restoredRevisionCount,
		bool $created,
		array $restoredPageIds
	): void {
		$content = $this->getPageContent( $restoredRev );
		// Bail out if we are not looking at an entity
		if ( !$content || !$this->entityContentFactory->isEntityContentModel( $content->getModel() ) ) {
			return;
		}

		$this->changeNotifier->notifyOnPageUndeleted( $restoredRev );
	}
}
