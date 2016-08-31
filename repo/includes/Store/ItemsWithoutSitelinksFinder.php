<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services for getting items without sitelinks.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch
 */
interface ItemsWithoutSitelinksFinder {

	/**
	 * Return all items without sitelinks
	 *
	 * @since 0.4
	 *
	 * @todo: move this to the SiteLinkLookup service
	 *
	 * @param string|null $siteId Restrict the request to a specific site.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getItemsWithoutSitelinks( $siteId = null, $limit = 50, $offset = 0 );

}
