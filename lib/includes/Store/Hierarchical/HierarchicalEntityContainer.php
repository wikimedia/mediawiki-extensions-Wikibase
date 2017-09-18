<?php

namespace Wikibase\Lib\Store\Hierarchical;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @todo Extract to a smaller component, e.g. the generic Wikibase DataModel.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface HierarchicalEntityContainer extends EntityDocument {

	/**
	 * @param HierarchicalEntityId $childId
	 *
	 * @throws OutOfBoundsException
	 * @return EntityDocument
	 */
	public function getChildEntity( HierarchicalEntityId $childId );

	/**
	 * @param EntityDocument $entity
	 */
	public function setChildEntity( EntityDocument $entity );

	/**
	 * @param HierarchicalEntityId $childId
	 */
	public function removeChildEntity( HierarchicalEntityId $childId );

}
