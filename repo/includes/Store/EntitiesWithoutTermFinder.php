<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services for getting entities without terms.
 *
 * @since 0.2
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch
 */
interface EntitiesWithoutTermFinder {

	/**
	 * Return all entities without a specify term
	 *
	 * @since 0.2
	 *
	 * @param string $termType Can be any member of the TermIndexEntry::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string|null $entityType Can be "item", "property" or "query". By default the search is done for all entities.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm( $termType, $language = null, $entityType = null, $limit = 50, $offset = 0 );

}
