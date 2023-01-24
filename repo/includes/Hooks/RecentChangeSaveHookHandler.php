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
use Wikibase\Lib\Changes\ItemChange;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;
use Wikibase\Repo\ChangeModification\DispatchChangesJob;
use Wikibase\Repo\Notifications\ChangeHolder;
use Wikibase\Repo\Store\Sql\SqlSubscriptionLookup;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\SubscriptionLookup;

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

		if (
			$logType === null ||
			( $logType === 'delete' && ( $logAction === 'restore' || $logAction === 'delete' ) )
		) {
			// Create a wikibase change either on edit or if the whole entity was (un)deleted.
			// Note: As entities can't be moved, we don't need to consider log action delete_redir/delete_redir2 here.
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

		if ( !$this->changeNeedsDispatching( $change ) ) {
			return;
		}

		$this->setChangeMetaData( $change, $recentChange, $centralUserId );
		$this->changeStore->saveChange( $change );

		$this->enqueueDispatchChangesJob(
			$change->getEntityId()->getSerialization()
		);
	}

	private function changeNeedsDispatching( EntityChange $change ) {
		return $this->subscriptionLookup->getSubscribers( $change->getEntityId() ) ||
			( $change instanceof ItemChange && $change->getSiteLinkDiff()->getOperations() );
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
