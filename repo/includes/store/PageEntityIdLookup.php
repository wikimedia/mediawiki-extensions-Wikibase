<?php

namespace Wikibase\Repo\Store;

use Title;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for a lookup that provides access to the ID of the entity
 * stored on a given page.
 *
 * The mapping may be implemented programmatically (e.g. by parsing the title as an EntityId),
 * or it may be based on an explicit mapping (e.g. in a database table).
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface PageEntityIdLookup {

	/**
	 * Returns the ID of the entity stored on the page identified by $title.
	 *
	 * @note There is no guarantee that the EntityId returned by this method refers to
	 * an existing entity.
	 *
	 * @param Title $title
	 *
	 * @todo: Switch this to using TitleValue once we can easily get the content model and
	 * handler based on a TitleValue.
	 *
	 * @return EntityId|null
	 */
	public function getPageEntityId( Title $title );

}
