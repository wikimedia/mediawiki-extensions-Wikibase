<?php

namespace Wikibase\Client\Hooks;

use Parser;
use ParserOutput;
use StripState;
use Title;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;
use Wikibase\NamespaceChecker;
use Wikibase\Updates\DataUpdateAdapter;
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
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();
		$usageUpdater = new UsageUpdater(
			$settings->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getStore()->getUsageTracker(),
			$wikibaseClient->getStore()->getUsageLookup(),
			$wikibaseClient->getStore()->getSubscriptionManager()
		);

		return new DataUpdateHookHandlers(
			$namespaceChecker,
			$usageUpdater
		);
	}

	/**
	 * Static handler for the ArticleEditUpdates hook.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ArticleEditUpdates
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

	public function __construct(
		NamespaceChecker $namespaceChecker,
		UsageUpdater $usageUpdater
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->usageUpdater = $usageUpdater;
	}

	/**
	 * Hook runs after internal parsing
	 *
	 * @param WikiPage $page The WikiPage object managing the edit
	 * @param object $editInfo The current edit info object.
	 *        $editInfo->output is an ParserOutput object.
	 * @param bool $changed False if this is a null edit
	 */
	public function doArticleEditUpdates( WikiPage $page, &$editInfo, $changed ) {
		$title = $page->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$usageAcc = new ParserOutputUsageAccumulator( $editInfo->output );

		$this->usageUpdater->updateUsageForPage(
			$title->getArticleId(),
			$usageAcc->getUsages()
		);
	}

}
