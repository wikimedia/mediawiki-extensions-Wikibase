<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services for getting items without sitelinks.
 *
 * @license GPL-2.0-or-later
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch
 */
interface ItemsWithoutSitelinksFinder {

	/**
	 * TODO: In the future, we probably want a non-numeric offset here, see T67333.
	 *
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getItemsWithoutSitelinks( $limit = 50, $offset = 0 );

}
