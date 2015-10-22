<?php

namespace Wikibase\Repo\Store;

use Wikibase\DataModel\Entity\EntityId;

/**
 * An in-memory cursor for paging through EntityIds.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InMemoryEntityIdPager implements EntityIdPager {

	/**
	 * @param EntityId[]
	 */
	private $entityIds;

	/**
	 * @param EntityId[] $entityIds
	 */
	public function __construct( array $entityIds ) {
		$this->entityIds = $entityIds;
	}

	/**
	 * See EntityIdPager::fetchIds
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[]
	 */
	public function fetchIds( $limit ) {
		return array_splice( $this->entityIds, 0, $limit );
	}

}
