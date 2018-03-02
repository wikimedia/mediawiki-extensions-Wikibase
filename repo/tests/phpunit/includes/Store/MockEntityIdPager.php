<?php

namespace Wikibase\Repo\Tests\Store;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\EntityId\EntityIdPager;

/**
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class MockEntityIdPager implements EntityIdPager {

	/**
	 * @var EntityId[]
	 */
	private $entityIds = [];

	/**
	 * @var int
	 */
	private $offset = 0;

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
		$entityIds = array_slice( $this->entityIds, $this->offset, $limit );
		$this->offset += count( $entityIds );
		return $entityIds;
	}

}
