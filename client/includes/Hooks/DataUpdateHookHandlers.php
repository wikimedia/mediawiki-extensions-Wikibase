<?php

namespace Wikibase\Client\Hooks;

use Content;
use LinksUpdate;
use ManualLogEntry;
use ParserCache;
use ParserOptions;
use ParserOutput;
use Title;
use User;
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

	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$usageUpdater = new UsageUpdater(
			$settings->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getStore()->getUsageTracker(),
			$wikibaseClient->getStore()->getUsageLookup(),
			$wikibaseClient->getStore()->getSubscriptionManager()
		);

		return new DataUpdateHookHandlers(
			$usageUpdater
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
	 * @param WikiPage $article
	 * @param User $user
	 * @param string $reason
	 * @param int $id id of the article that was deleted
	 * @param Content $content
	 * @param ManualLogEntry $logEntry
	 */
	public static function onArticleDeleteComplete(
		WikiPage &$article,
		User &$user,
		$reason,
		$id,
		Content $content,
		ManualLogEntry $logEntry
	) {
		$title = $article->getTitle();

		$handler = self::newFromGlobalState();
		$handler->doArticleDeleteComplete( $title->getNamespace(), $id, $logEntry->getTimestamp() );
	}

	/**
	 * Static handler for ParserCacheSaveComplete
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserCacheSaveComplete
	 * @see doParserCacheSaveComplete
	 *
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
		UsageUpdater $usageUpdater
	) {
		$this->usageUpdater = $usageUpdater;
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
	 * @param $title $title
	 */
	public function doParserCacheSaveComplete( ParserOutput $parserOutput, Title $title ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );

		// The parser output should tell us when it was parsed. If not, ask the Title object.
		// These timestamps should usually be the same, but asking $title may cause a database query.
		$touched = $parserOutput->getTimestamp() ?: $title->getTouched();

		// Add or touch any usages present in the new rendering.
		// This allows us to track usages in each user language separately, for multilingual sites.
		$this->usageUpdater->addUsagesForPage(
			$title->getArticleId(),
			$usageAcc->getUsages(),
			$touched
		);
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
