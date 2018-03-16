<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 3.10
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
interface LabelLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return Term|null
	 */
	public function getLabel( EntityId $entityId );

}
