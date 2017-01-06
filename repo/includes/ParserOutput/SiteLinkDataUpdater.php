<?php

namespace Wikibase\Repo\ParserOutput;

use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface SiteLinkDataUpdater extends ParserOutputDataUpdater {

	/**
	 * Extract some data or do processing on a SiteLink during parsing.
	 *
	 * This method is invoked when processing a SiteLinkList on an Item,
	 * or other entity type that has SiteLinks.
	 *
	 * @param SiteLink $siteLink
	 */
	public function processSiteLink( SiteLink $siteLink );

}
