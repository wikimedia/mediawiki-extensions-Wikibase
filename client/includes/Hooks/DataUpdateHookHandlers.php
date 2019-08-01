<?php

namespace Wikibase\Client\Hooks;

use Content;
use DeferredUpdates;
use InvalidArgumentException;
use JobQueueGroup;
use LinksUpdate;
use LogEntry;
use ParserCache;
use ParserOptions;
use ParserOutput;
use Title;
use User;
use Wikibase\Client\Store\AddUsagesForPageJob;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\EntityUsage;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\WikibaseClient;
use WikiPage;

/**
 * Hook handlers for triggering data updates.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of DataUpdateHookHandlers and then call the
 * corresponding member function on that.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 * @author Marius Hoch
 */
class DataUpdateHookHandlers {

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
	 * @return self
	 */
	public static function newFromGlobalState() {
		return new self(
			WikibaseClient::getDefaultInstance()->getStore()->getUsageUpdater(),
			JobQueueGroup::singleton(),
			WikibaseClient::getDefaultInstance()->getStore()->getUsageLookup()
		);
	}

	/**
	 * Static handler for the LinksUpdateComplete hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/LinksUpdateComplete
	 * @see doLinksUpdateComplete
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public static function onLinksUpdateComplete( LinksUpdate $linksUpdate ) {
		$handler = self::newFromGlobalState();
		$handler->doLinksUpdateComplete( $linksUpdate );
	}

	/**
	 * Static handler for ArticleDeleteComplete
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleDeleteComplete
	 * @see doArticleDeleteComplete
	 *
	 * @param WikiPage &$wikiPage
	 * @param User &$user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content|null $content
	 * @param LogEntry $logEntry
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$wikiPage,
		User &$user,
		$reason,
		$id,
		Content $content = null,
		LogEntry $logEntry
	) {
		$title = $wikiPage->getTitle();

		DeferredUpdates::addCallableUpdate( function() use ( $title, $id ) {
			$handler = self::newFromGlobalState();
			$handler->doArticleDeleteComplete( $title->getNamespace(), $id );
		} );
	}

	/**
	 * Static handler for ParserCacheSaveComplete
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserCacheSaveComplete
	 * @see doParserCacheSaveComplete
	 *
	 * @param ParserCache $parserCache
	 * @param ParserOutput $pout
	 * @param Title $title
	 * @param ParserOptions $pops
	 * @param int $revId
	 */
	public static function onParserCacheSaveComplete(
		ParserCache $parserCache,
		ParserOutput $pout,
		Title $title,
		ParserOptions $pops,
		$revId
	) {
		$handler = self::newFromGlobalState();
		$handler->doParserCacheSaveComplete( $pout, $title );
	}

	public function __construct(
		UsageUpdater $usageUpdater,
		JobQueueGroup $jobScheduler,
		UsageLookup $usageLookup
	) {
		$this->usageUpdater = $usageUpdater;
		$this->jobScheduler = $jobScheduler;
		$this->usageLookup = $usageLookup;
	}

	/**
	 * Triggered when a page gets re-rendered to update secondary link tables.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param LinksUpdate $linksUpdate
	 */
	public function doLinksUpdateComplete( LinksUpdate $linksUpdate ) {
		$title = $linksUpdate->getTitle();

		$parserOutput = $linksUpdate->getParserOutput();
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );

		// Please note that page views that happen between the page save but before this is run will have
		// their usages removed (as we might add the usages via doParserCacheSaveComplete before this is run).
		$this->usageUpdater->replaceUsagesForPage( $title->getArticleID(), $usageAcc->getUsages() );
	}

	/**
	 * Triggered when a new rendering of a page is committed to the ParserCache.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 */
	public function doParserCacheSaveComplete( ParserOutput $parserOutput, Title $title ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );

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
		$this->jobScheduler->lazyPush( $addUsagesForPageJob );
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

	/**
	 * Triggered after a page was deleted.
	 * Implemented to prune usage tracking information via UsageUpdater.
	 *
	 * @param int $namespace
	 * @param int $pageId
	 */
	public function doArticleDeleteComplete( $namespace, $pageId ) {
		$this->usageUpdater->pruneUsagesForPage( $pageId );
	}

}
