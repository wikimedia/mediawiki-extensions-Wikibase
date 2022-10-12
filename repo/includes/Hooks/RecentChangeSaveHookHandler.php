<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use CentralIdLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookupFactory;
use RecentChange;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Store\Sql\EntityChangeLookup;
use Wikibase\Repo\ChangeModification\DispatchChangesJob;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Store\Store;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
/**
 * Nasty hack to inject information from RC into the change notification saved earlier
 * by the onRevisionFromEditComplete hook handler.
 *
 * @license GPL-2.0-or-later
 */
class RecentChangeSaveHookHandler {

	private $changeLookup;

	private $changeStore;

	private $centralIdLookup;

	public function __construct(
		EntityChangeLookup $changeLookup,
		ChangeStore $changeStore,
		?CentralIdLookup $centralIdLookup
	) {
		$this->changeLookup = $changeLookup;
		$this->changeStore = $changeStore;
		$this->centralIdLookup = $centralIdLookup;
	}

	public static function factory(
		CentralIdLookupFactory $centralIdLookupFactory,
		Store $store
	): self {
		return new self(
			$store->getEntityChangeLookup(),
			$store->getChangeStore(),
			$centralIdLookupFactory->getNonLocalLookup()
		);
	}

	public function onRecentChange_save( RecentChange $recentChange ): void {
		$logType = $recentChange->getAttribute( 'rc_log_type' );
		$logAction = $recentChange->getAttribute( 'rc_log_action' );
		$revId = $recentChange->getAttribute( 'rc_this_oldid' );

		if ( $revId <= 0 ) {
			// If we don't have a revision ID, we have no chance to find the right change to update.
			// NOTE: As of February 2015, RC entries for undeletion have rc_this_oldid = 0.
			return;
		}

		if ( $logType === null || ( $logType === 'delete' && $logAction === 'restore' ) ) {
			$change = $this->changeLookup->loadByRevisionId( $revId, EntityChangeLookup::FROM_MASTER );

			if ( $change ) {
				if ( $this->centralIdLookup === null ) {
					$centralUserId = 0;
				} else {
					$centralUserId = $this->centralIdLookup->centralIdFromLocalUser(
						$recentChange->getPerformerIdentity()
					);
				}

				$this->setChangeMetaData( $change, $recentChange, $centralUserId );
				$this->changeStore->saveChange( $change );
			}
		}
	}

	private function setChangeMetaData( EntityChange $change, RecentChange $rc, int $centralUserId ): void {
		$change->setFields( [
			ChangeRow::REVISION_ID => $rc->getAttribute( 'rc_this_oldid' ),
			ChangeRow::TIME => $rc->getAttribute( 'rc_timestamp' ),
		] );

		$change->setMetadata( [
			'bot' => $rc->getAttribute( 'rc_bot' ),
			'page_id' => $rc->getAttribute( 'rc_cur_id' ),
			'rev_id' => $rc->getAttribute( 'rc_this_oldid' ),
			'parent_id' => $rc->getAttribute( 'rc_last_oldid' ),
			'comment' => $rc->getAttribute( 'rc_comment' ),
		] );

		$change->addUserMetadata(
			$rc->getAttribute( 'rc_user' ),
			$rc->getAttribute( 'rc_user_text' ),
			$centralUserId
		);
	}

	private function enqueueDispatchChangesJob( string $entityIdSerialization ): void {
		$job = DispatchChangesJob::makeJobSpecification( $entityIdSerialization );
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
		$jobQueueGroup->lazyPush( $job );
	}

}
