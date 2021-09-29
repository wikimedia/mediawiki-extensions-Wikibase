<?php

declare( strict_types=1 );

namespace Wikibase\Repo\Hooks;

use CentralIdLookup;
use MediaWiki\MediaWikiServices;
use MediaWiki\User\CentralId\CentralIdLookupFactory;
use RecentChange;
use Wikibase\Lib\Changes\Change;
use Wikibase\Lib\Changes\ChangeRow;
use Wikibase\Lib\Changes\ChangeStore;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Repo\ChangeModification\DispatchChangesJob;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\SubscriptionLookup;
use Wikibase\Repo\WikibaseRepo;

//phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName
/**
 * Inject change information from RC into the change notification created
 * by the onRevisionFromEditComplete hook handler and save it to wb_changes table.
 *
 * @license GPL-2.0-or-later
 */
class RecentChangeSaveHookHandler {

	private $changeStore;

	private $centralIdLookup;

	private $changeHolder;

	private $subscriptionLookup;

	public function __construct(
		ChangeStore $changeStore,
		ChangeHolder $changeHolder,
		SubscriptionLookup $subscriptionLookup,
		?CentralIdLookup $centralIdLookup
	) {
		$this->changeStore = $changeStore;
		$this->centralIdLookup = $centralIdLookup;
		$this->changeHolder = $changeHolder;
		$this->subscriptionLookup = $subscriptionLookup;
	}

	public static function factory(
		CentralIdLookupFactory $centralIdLookupFactory,
		ChangeHolder $changeHolder,
		RepoDomainDbFactory $repoDomainDbFactory,
		Store $store
	): self {
		return new self(
			$store->getChangeStore(),
			$changeHolder,
			new SqlSubscriptionLookup( $repoDomainDbFactory->newRepoDb() ),
			$centralIdLookupFactory->getNonLocalLookup()
		);
	}

	public function onRecentChange_save( RecentChange $recentChange ): void {
		$logType = $recentChange->getAttribute( 'rc_log_type' );
		$logAction = $recentChange->getAttribute( 'rc_log_action' );

		if ( $recentChange->getAttribute( 'rc_this_oldid' ) <= 0 ) {
			// If we don't have a revision ID, we have no chance to find the right change to update.
			// NOTE: As of February 2015, RC entries for undeletion have rc_this_oldid = 0.
			return;
		}

		if ( $logType === null || ( $logType === 'delete' && $logAction === 'restore' ) ) {
			foreach ( $this->changeHolder->getChanges() as $change ) {
				$this->handleChange( $change, $recentChange );
			}
		}
	}

	private function handleChange( Change $change, RecentChange $recentChange ) {
		if ( $this->centralIdLookup === null ) {
			$centralUserId = 0;
		} else {
			$centralUserId = $this->centralIdLookup->centralIdFromLocalUser(
				$recentChange->getPerformerIdentity()
			);
		}

		if ( !$change instanceof EntityChange ) {
			return;
		}

		if ( !$this->subscriptionLookup->getSubscribers( $change->getEntityId() ) ) {
			return;
		}

		$this->setChangeMetaData( $change, $recentChange, $centralUserId );
		$this->changeStore->saveChange( $change );

		// FIXME: inject settings instead?
		if ( WikibaseRepo::getSettings()->getSetting( 'dispatchViaJobsEnabled' ) ) {
			$this->enqueueDispatchChangesJob(
				$change->getEntityId()->getSerialization(),
				$change->getId()
			);
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

	private function enqueueDispatchChangesJob( string $entityIdSerialization, int $changeId ): void {
		$job = DispatchChangesJob::makeJobSpecification( $entityIdSerialization, $changeId );
		$jobQueueGroup = MediaWikiServices::getInstance()->getJobQueueGroupFactory()->makeJobQueueGroup();
		$jobQueueGroup->lazyPush( $job );
	}

}
