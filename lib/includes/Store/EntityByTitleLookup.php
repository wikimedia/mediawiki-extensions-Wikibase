<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for services that allow Entities to be found by page title.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface EntityByTitleLookup {

	/**
	 * Returns the id of the entity that associated to the given page title.
	 * How the entity would be associated is not specified by this interface.
	 * A typical mechanism would be SiteLinks via the mapping implemented by a SiteLinkLookup.
	 *
	 * @param string $globalSiteId
	 * @param string $pageTitle
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForLink( $globalSiteId, $pageTitle );

}
