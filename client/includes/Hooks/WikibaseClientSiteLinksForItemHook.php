<?php

namespace Wikibase\Client\Hooks;

use Wikibase\Client\Usage\UsageAccumulator;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\SiteLink;

/**
 * This is a hook handler interface, see docs/Hooks.md in MediaWiki core.
 * Use the hook name "WikibaseClientSiteLinksForItem" to register
 * handlers implementing this interface.
 *
 * @license GPL-2.0-or-later
 */
interface WikibaseClientSiteLinksForItemHook {

	/**
	 * Called by Wikibase::Client::Hooks::SiteLinksForDisplayLookup to allow altering the sitelinks used
	 * in language links and the other project's sidebar.
	 * Only called in case the page we are on is linked with an item.
	 *
	 * @param Item $item Item the page is linked with.
	 * @param SiteLink[] &$siteLinks Array containing the site links to display indexed by site global ID.
	 * @param UsageAccumulator $usageAccumulator A usage accumulator to track the usages of Wikibase entities done by
	 *        the hook handlers.
	 */
	public function onWikibaseClientSiteLinksForItem(
		Item $item,
		array &$siteLinks,
		UsageAccumulator $usageAccumulator
	): void;

}
