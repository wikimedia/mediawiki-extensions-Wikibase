<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * The position markers are implementation dependent and are not
 * interchangeable between different implementations.
 *
 * @since 3.14
 *
 * @author Addshore
 * @author Jeroen De Dauw
 * @license GPL-2.0-or-later
 */
class InMemoryEntityIdPager implements SeekableEntityIdPager {

	/**
	 * @var EntityId[]
	 */
	private $entityIds = [];

	/**
	 * @var int
	 */
	private $offset = 0;

	/**
	 * @param EntityId ...$ids
	 */
	public function __construct( ...$ids ) {
		$this->entityIds = $ids;
	}

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

	public function getPosition() {
		return $this->offset;
	}

	public function setPosition( $position ) {
		$this->offset = $position;
	}

}
