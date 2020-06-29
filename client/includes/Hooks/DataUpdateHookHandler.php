<?php

namespace Wikibase\Client\Hooks;

use Content;
use DeferredUpdates;
use ExtensionRegistry;
use InvalidArgumentException;
use JobQueueGroup;
use LinksUpdate;
use MediaWiki\Hook\LinksUpdateCompleteHook;
use MediaWiki\Hook\ParserCacheSaveCompleteHook;
use MediaWiki\Page\Hook\ArticleDeleteCompleteHook;
use ParserCache;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\WikibaseClient;
use WikiPage;

/**
 * Hook handlers for triggering data updates.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of DataUpdateHookHandler and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DataUpdateHookHandler implements
				LinksUpdateCompleteHook,
				ArticleDeleteCompleteHook,
				ParserCacheSaveCompleteHook
{

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	/**
	 * @var JobQueueGroup
	 */
	private $jobScheduler;

	/**
	 * @var UsageLookup
	 */
	private $usageLookup;

	/**
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	public static function newFromGlobalState(): self {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getStore()->getUsageUpdater(),
			JobQueueGroup::singleton(),
			$wikibaseClient->getStore()->getUsageLookup(),
			new EntityUsageFactory( $wikibaseClient->getEntityIdParser() )
		);
	}

	public function __construct(
		UsageUpdater $usageUpdater,
		JobQueueGroup $jobScheduler,
		UsageLookup $usageLookup,
		EntityUsageFactory $entityUsageFactory
	) {
		$this->usageUpdater = $usageUpdater;
		$this->jobScheduler = $jobScheduler;
		$this->usageLookup = $usageLookup;
		$this->entityUsageFactory = $entityUsageFactory;
	}

	/**
	 * Static handler for ArticleDeleteComplete
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 *
	 * @param WikiPage $wikiPage WikiPage that was deleted
	 * @param User $user User that deleted the article
	 * @param string $reason Reason the article was deleted
	 * @param int $id ID of the article that was deleted
	 * @param Content|null $content Content of the deleted page (or null, when deleting a broken page)
	 * @param \ManualLogEntry $logEntry ManualLogEntry used to record the deletion
	 * @param int $archivedRevisionCount Number of revisions archived during the deletion
	 */
	public function onArticleDeleteComplete( $wikiPage, $user, $reason, $id,
		$content, $logEntry, $archivedRevisionCount
	): void {
		DeferredUpdates::addCallableUpdate( function () use ( $id ) {
			$this->usageUpdater->pruneUsagesForPage( $id );
		} );
	}

	/**
	 * Triggered when a page gets re-rendered to update secondary link tables.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param LinksUpdate $linksUpdate
	 * @param mixed $ticket Prior result of LBFactory::getEmptyTransactionTicket()
	 */
	public function onLinksUpdateComplete( $linksUpdate, $ticket ): void {
		// Tests fail because when repo is not loaded, it tries to connect to repo's database
		if (
			!ExtensionRegistry::getInstance()->isLoaded( 'WikibaseRepository' ) &&
			defined( 'MW_PHPUNIT_TEST' )
		) {
			return;
		}

		$this->doLinksUpdateComplete( $linksUpdate );
	}

	public function doLinksUpdateComplete( LinksUpdate $linksUpdate ): void {
		$title = $linksUpdate->getTitle();

		$parserOutput = $linksUpdate->getParserOutput();
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput, $this->entityUsageFactory );

		// Please note that page views that happen between the page save but before this is run will have
		// their usages removed (as we might add the usages via onParserCacheSaveComplete before this is run).
		$this->usageUpdater->replaceUsagesForPage( $title->getArticleID(), $usageAcc->getUsages() );
	}

	/**
	 * Triggered when a new rendering of a page is committed to the ParserCache.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param ParserCache $parserCache ParserCache object $parserOutput was stored in
	 * @param ParserOutput $parserOutput ParserOutput object that was stored
	 * @param Title $title Title of the page that was parsed to generate $parserOutput
	 * @param ParserOptions $popts ParserOptions used for generating $parserOutput
	 * @param int $revId ID of the revision that was parsed to create $parserOutput
	 */
	public function onParserCacheSaveComplete( $parserCache, $parserOutput, $title, $popts, $revId ): void {
		DeferredUpdates::addCallableUpdate( function () use ( $parserOutput, $title ) {
			$usageAcc = new ParserOutputUsageAccumulator( $parserOutput, $this->entityUsageFactory );

			$usages = $this->reindexEntityUsages( $usageAcc->getUsages() );
			if ( $usages === [] ) {
				// no usages or no title, bail out
				return;
			}

			// Add any usages present in the new rendering.
			// This allows us to track usages in each user language separately, for multilingual sites.

			// NOTE: Since parser cache updates may be triggered by page views (in a new language),
			// schedule the usage updates in the job queue, to avoid writing to the database
			// during a GET request.

			$currentUsages = $this->reindexEntityUsages(
				$this->usageLookup->getUsagesForPage( $title->getArticleID() )
			);
			$newUsages = array_diff_key( $usages, $currentUsages );
			if ( $newUsages === [] ) {
				return;
			}

			$addUsagesForPageJob = AddUsagesForPageJob::newSpec( $title, $newUsages );
			$this->jobScheduler->push( $addUsagesForPageJob );
		} );
	}

	/**
	 * Re-indexes the given list of EntityUsages so that each EntityUsage can be found by using its
	 * string representation as a key.
	 *
	 * @param EntityUsage[] $usages
	 *
	 * @throws InvalidArgumentException
	 * @return EntityUsage[]
	 */
	private function reindexEntityUsages( array $usages ) {
		$reindexed = [];

		foreach ( $usages as $usage ) {
			if ( !( $usage instanceof EntityUsage ) ) {
				throw new InvalidArgumentException( '$usages must contain EntityUsage objects.' );
			}

			$key = $usage->getIdentityString();
			$reindexed[$key] = $usage;
		}

		return $reindexed;
	}

}
