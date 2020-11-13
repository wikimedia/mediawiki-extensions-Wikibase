<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * Looks up the label or description of an entity.
 *
 * Like for {@link LabelLookup},
 * it depends on the implementation whether language fallbacks are applied or not.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
interface LabelDescriptionLookup extends LabelLookup {

	/**
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return Term|null
	 */
	public function getDescription( EntityId $entityId );

}
