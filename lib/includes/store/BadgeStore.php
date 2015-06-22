<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\SiteLink;

/**
 * Contains methods for write actions on the badge store.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface BadgeStore extends BadgeLookup {

	/**
	 * Saves the badges for the provided sitelink.
	 *
	 * @since 0.5
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return boolean Success indicator
	 */
	public function saveBadgesOfSiteLink( SiteLink $siteLink );

	/**
	 * Removes the links for the provided item.
	 *
	 * @since 0.5
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return boolean Success indicator
	 */
	public function deleteBadgesOfSiteLink( SiteLink $siteLink );

	/**
	 * Clears all badges from the cache.
	 *
	 * @since 0.5
	 *
	 * @return boolean Success indicator
	 */
	public function clear();

}
