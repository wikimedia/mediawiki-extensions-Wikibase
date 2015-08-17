<?php

namespace Wikibase\DataModel\Services\Lookup;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
interface LabelDescriptionLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @throws EntityIdLookupException
	 * @return Term|null
	 */
	public function getLabel( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 *
	 * @throws EntityIdLookupException
	 * @return Term|null
	 */
	public function getDescription( EntityId $entityId );

}
