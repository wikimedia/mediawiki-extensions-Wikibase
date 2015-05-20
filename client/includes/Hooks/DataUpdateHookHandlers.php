<?php

namespace Wikibase\Client\Hooks;

use Content;
use ManualLogEntry;
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
	 * Static handler for the ArticleEditUpdates hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleEditUpdates
	 * @see doArticleEditUpdates
	 *
	 * @param WikiPage $page The WikiPage object managing the edit
	 * @param object $editInfo The current edit info object.
	 *        $editInfo->output is an ParserOutput object.
	 * @param bool $changed False if this is a null edit
	 */
	public static function onArticleEditUpdates( WikiPage $page, &$editInfo, $changed ) {
		$handler = self::newFromGlobalState();
		$handler->doArticleEditUpdates( $page, $editInfo, $changed );
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
	 *
	 * @return bool
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

	public function __construct(
		UsageUpdater $usageUpdater
	) {
		$this->usageUpdater = $usageUpdater;
	}

	/**
	 * Hook run after a new revision was stored.
	 * Implemented to update usage tracking information via UsageUpdater.
	 *
	 * @param WikiPage $page The WikiPage object managing the edit
	 * @param object $editInfo The current edit info object.
	 *        $editInfo->output is an ParserOutput object.
	 * @param bool $changed False if this is a null edit
	 */
	public function doArticleEditUpdates( WikiPage $page, &$editInfo, $changed ) {
		$title = $page->getTitle();

		$usageAcc = new ParserOutputUsageAccumulator( $editInfo->output );

		// Add or touch any usages present in the new revision
		$this->usageUpdater->addUsagesForPage(
			$title->getArticleId(),
			$usageAcc->getUsages(),
			$page->getTouched()
		);

		// Prune any usages older than the new revision's timestamp.
		// NOTE: only prune after adding the new updates, to avoid unsubscribing and then
		// immediately re-subscribing to the used entities.
		$this->usageUpdater->pruneUsagesForPage(
			$title->getArticleId(),
			$page->getTouched()
		);
	}

	/**
	 * Hook run after a page was deleted.
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
