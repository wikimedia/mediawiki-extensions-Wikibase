<?php

namespace Wikibase\Repo\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;

/**
 * @license GPL-2.0+
 * @author Addshore
 */
class MockEntityIdPager implements EntityIdPager {

	/**
	 * @var EntityId[]
	 */
	private $entityIds = [];

	/**
	 * @param EntityId $entityId
	 */
	public function addEntityId( EntityId $entityId ) {
		$this->entityIds[] = $entityId;
	}

	/**
	 * @see EntityIdPager::fetchIds
	 *
	 * @param int $limit
	 *
	 * @return EntityId[]
	 */
	public function fetchIds( $limit ) {
		return array_slice( $this->entityIds, 0, $limit );
	}

}
