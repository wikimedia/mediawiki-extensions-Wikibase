<?php

namespace Wikibase\Client\Hooks;

use Content;
use EnqueueJob;
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
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use WikiPage;

/**
 * Hook handlers for triggering data updates.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of DataUpdateHookHandlers and then call the
 * corresponding member function on that.
 *
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
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

	public static function newFromGlobalState() {
		return new DataUpdateHookHandlers(
			WikibaseClient::getDefaultInstance()->getStore()->getUsageUpdater(),
			JobQueueGroup::singleton()
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
	 * @param Content $content
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

		$handler = self::newFromGlobalState();
		$handler->doArticleDeleteComplete( $title->getNamespace(), $id, $logEntry->getTimestamp() );
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
		JobQueueGroup $jobScheduler
	) {
		$this->usageUpdater = $usageUpdater;
		$this->jobScheduler = $jobScheduler;
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

		// For now, use the current timestamp as the touch date.
		// $parserOutput->getTimestamp() sounds good, but is documented as "timestamp of the revision",
		// which is not what we want. $title->getTouched() sounds good, but it may not have been
		// updated reflecting the current run of LinksUpdate yet. Since on LinksUpdateComplete we
		// actually want to purge all old tracking entries and only care about keeping the ones
		// now present in $parserOutput, using the current timestamp should be fine.
		// NOTE: adjust DataUpdateHookHandlerTest::newUsageUpdater when fixing this.
		$touched = wfTimestampNow();

		// Add or touch any usages present in the rendering
		$this->usageUpdater->addUsagesForPage(
			$title->getArticleId(),
			$usageAcc->getUsages(),
			$touched
		);

		// Prune any usages older than the new rendering's timestamp.
		// NOTE: only prune after adding the new updates, to avoid unsubscribing and then
		// immediately re-subscribing to the used entities.
		$this->usageUpdater->pruneUsagesForPage(
			$title->getArticleId(),
			$touched
		);
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

		// Note: ParserOutput::getTimestamp() is unreliable and "sometimes" contains an old timestamp.
		// Note: getTouched() returns false if $title doesn't exist.
		$touched = $title->getTouched();

		if ( !$touched || count( $usageAcc->getUsages() ) === 0 ) {
			// no usages or no title, bail out
			return;
		}

		// Add or touch any usages present in the new rendering.
		// This allows us to track usages in each user language separately, for multilingual sites.

		// NOTE: Since parser cache updates may be triggered by page views (in a new language),
		// schedule the usage updates in the job queue, to avoid writing to the database
		// during a GET request.

		//TODO: Before posting a job, check slave database. If no changes are needed, skip update.

		$addUsagesForPageJob = AddUsagesForPageJob::newSpec( $title, $usageAcc->getUsages(), $touched );
		$enqueueJob = EnqueueJob::newFromLocalJobs( $addUsagesForPageJob );

		$this->jobScheduler->lazyPush( $enqueueJob );
	}

	/**
	 * Triggered after a page was deleted.
	 * Implemented to prune usage tracking information via UsageUpdater.
	 *
	 * @param int $namespace
	 * @param int $pageId
	 * @param string $timestamp
	 */
	public function doArticleDeleteComplete( $namespace, $pageId, $timestamp ) {
		$this->usageUpdater->pruneUsagesForPage( $pageId, $timestamp );
	}

}
