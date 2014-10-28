<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface TermLookup {

	/**
	 * Gets terms of an Entity with the given EnitityId.
	 *
	 * @param EntityId $entityId
	 *
	 * @return Wikibase\Term[]
	 */
	public function getTermsOfEntity( EntityId $entityId );

}
