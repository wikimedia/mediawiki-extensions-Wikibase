<?php

namespace Wikibase\Lib\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface TermsLookup {

	/**
	 * @param EntityId $entityId
	 * @param string $termType
	 *
	 * @return TermList
	 */
	public function getTermsByTermType( EntityId $entityId, $termType );

}
