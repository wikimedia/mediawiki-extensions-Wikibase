<?php

namespace Wikibase\Lib\Store\Hierarchical;

use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @todo Extract to a smaller component, e.g. the generic Wikibase DataModel.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 * @author Thiemo Kreuz
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
	 * @param EntityDocument $childEntity
	 */
	public function setChildEntity( EntityDocument $childEntity );

	/**
	 * @param HierarchicalEntityId $childId
	 */
	public function removeChildEntity( HierarchicalEntityId $childId );

}
