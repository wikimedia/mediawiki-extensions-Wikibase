<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * Contains methods to lookup badges.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
interface BadgeLookup {

	/**
	 * Returns the list of badges assigned to the given sitelink.
	 *
	 * @since 0.5
	 *
	 * @param SiteLink $siteLink
	 *
	 * @return ItemId[]
	 */
	public function getBadgesForSiteLink( SiteLink $siteLink );

	/**
	 * Returns the list of items which have a badge set
	 * for the sitelink of the given site id.
	 *
	 * @since 0.5
	 *
	 * @param ItemId $badge
	 * @param string|null $siteId
	 *
	 * @return SiteLink[]
	 */
	public function getSiteLinksForBadge( ItemId $badge, $siteId = null );

}
