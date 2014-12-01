<?php

namespace Wikibase\Lib\Store;

use TitleValue;
use Wikibase\DataModel\Entity\EntityId;

/**
 * Service interface for determining the ID of the entity associated with the given page title.
 * Note that depending on implementation and context, this may be the entity defined on the given
 * page, or the entity associated with the given page via sitelink.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
interface EntityIdLookup {

	/**
	 * Returns the ID of the entity associated with the given page title.
	 *
	 * The interpretation of what "associated" means in this context is left to the
	 * implementations. Calling code should make sure to use an EntityIdLookup instance
	 * that implements the desired semantics (e.g. returning the entity define on a given
	 * page vs. the entity referencing the given page via sitelink).
	 *
	 * @note There is no guarantee that the EntityId returned by this method refers to
	 * an existing entity.
	 *
	 * @param TitleValue $title
	 *
	 * @return EntityId|null
	 */
	public function getEntityIdForTitle( TitleValue $title );

}
