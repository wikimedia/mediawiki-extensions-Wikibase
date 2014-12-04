<?php

namespace Wikibase\Lib\Store;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
interface LabelLookup {

	/**
	 * @param EntityId $entityId
	 *
	 * @throws OutOfBoundsException if no such label or entity could be found
	 * @return string
	 */
	public function getLabel( EntityId $entityId );

}
