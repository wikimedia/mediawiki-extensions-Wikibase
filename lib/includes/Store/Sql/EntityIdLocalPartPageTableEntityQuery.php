<?php

namespace Wikibase\Lib\Store\Sql;

use stdClass;
use Wikibase\DataModel\Entity\EntityId;

/**
 * PageTableEntityQuery that assumes the entity IDs "localPart" matches page_title of the page
 * that the entity is stored on.
 *
 * For example: An Item with ID Q1 is commonly stored on a wikipage with title Q1
 *
 * @license GPL-2.0-or-later
 */
class EntityIdLocalPartPageTableEntityQuery extends PageTableEntityQueryBase {

	/**
	 * @param EntityId $entityId
	 * @return array SQL condition
	 */
	protected function getConditionForEntityId( EntityId $entityId ) {
		return [ 'page_title' => $entityId->getSerialization() ];
	}

	/** @inheritDoc */
	protected function getEntityIdStringFromRow( stdClass $row ) {
		return $row->page_title;
	}

	/** @inheritDoc */
	protected function getFieldsNeededForMapping() {
		return [ 'page_title' ];
	}

}
