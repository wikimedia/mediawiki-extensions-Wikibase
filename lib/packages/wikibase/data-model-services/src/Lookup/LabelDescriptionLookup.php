<?php

namespace Wikibase\DataModel\Services\Lookup;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 1.1
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
interface LabelDescriptionLookup {

	/**
	 * @since 2.0
	 *
	 * @param EntityId $entityId
	 *
	 * @throws LabelDescriptionLookupException
	 * @return Term|null
	 */
	public function getLabel( EntityId $entityId );

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
