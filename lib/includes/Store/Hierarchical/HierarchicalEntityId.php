<?php

namespace Wikibase\Lib\Store\Hierarchical;

use Wikibase\DataModel\Entity\EntityId;

/**
 * @todo Extract to a smaller component, e.g. the generic Wikibase DataModel.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
abstract class HierarchicalEntityId extends EntityId {

	/**
	 * An EntityId that is not hierarchical and can not return it's direct parent ID must not
	 * implement the hierarchical interface at all.
	 *
	 * @return EntityId
	 */
	abstract public function getParentId();

}
