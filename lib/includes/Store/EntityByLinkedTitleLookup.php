<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface to find Entities by a (somehow) linked page title that is not identical to the
 * page where the entity is stored. Not meant as a replacement for EntityIdLookup!
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
 */
interface EntityByLinkedTitleLookup {

	/**
	 * Returns the ID of an Entity associated to the given page title. How the Entity is associated
	 * is not specified by this interface. A typical mechanism is the mapping provided via SiteLinks
	 * on Items.
	 *
	 * @param string $globalSiteId An empty string refers to the local wiki.
	 * @param string $pageTitle
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForLinkedTitle( $globalSiteId, $pageTitle );

}
