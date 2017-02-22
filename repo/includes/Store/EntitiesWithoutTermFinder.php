<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * Interface for services for getting entities without terms.
 *
 * @license GPL-2.0+
 * @author Thomas Pellissier Tanon
 * @author Marius Hoch
 */
interface EntitiesWithoutTermFinder {

	/**
	 * @param string $termType Can be any member of the TermIndexEntry::TYPE_ enum
	 * @param string|null $language Restrict the search for one language. By default the search is done for all languages.
	 * @param string[]|null $entityTypes Array containing the entity types to search for, typically
	 *  "item" and/or "property". Null includes all indexed entity types.
	 * @param integer $limit Limit of the query.
	 * @param integer $offset Offset of the query.
	 *
	 * @return EntityId[]
	 */
	public function getEntitiesWithoutTerm(
		$termType,
		$language = null,
		array $entityTypes = null,
		$limit = 50,
		$offset = 0
	);

}
