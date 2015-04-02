<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\Term;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
interface LabelDescriptionLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException if no such label or entity could be found
	 * @return Term
	 */
	public function getLabel( EntityId $entityId );

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException if no such description or entity could be found
	 * @return Term
	 */
	public function getDescription( EntityId $entityId );

}
