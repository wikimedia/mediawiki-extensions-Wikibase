<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * Looks up the label of an entity.
 *
 * The language used is typically determined when the service is constructed,
 * such as the content or interface language.
 * Whether language fallbacks are applied or not depends on the implementation.
 * (To look up labels in specific languages, without fallbacks, use {@link TermLookup}.)
 *
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
