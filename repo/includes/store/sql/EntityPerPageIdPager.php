<?php

namespace Wikibase\Repo\Store\SQL;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Repo\Store\EntityPerPage;

/**
 * EntityPerPageIdPager is a cursor for iterating over batches of EntityIds from an
 * EntityPerPage service.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class EntityPerPageIdPager implements EntityIdPager {

	/**
	 * @var EntityPerPage
	 */
	protected $entityPerPage;

	/**
	 * @var string|null
	 */
	protected $entityType;

	/**
	 * @var EntityId|null
	 */
	protected $position = null;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param null|string $entityType The desired entity type, or null for any type.
	 */
	public function __construct( EntityPerPage $entityPerPage, $entityType = null ) {
		$this->entityPerPage = $entityPerPage;
		$this->entityType = $entityType;
	}

	/**
	 * Fetches the next batch of IDs. Calling this has the side effect of advancing the
	 * internal state of the page, typically implemented by some underlying resource
	 * such as a file pointer or a database connection.
	 *
	 * @note: After some finite number of calls, this method should eventually return
	 * an empty list of IDs, indicating that no more IDs are available.
	 *
	 * @since 0.5
	 *
	 * @param int $limit The maximum number of IDs to return.
	 *
	 * @return EntityId[] A list of EntityIds matching the given parameters. Will
	 * be empty if there are no more entities to list from the given offset.
	 */
	public function fetchIds( $limit ) {
		$ids = $this->entityPerPage->listEntities( $this->entityType, $limit, $this->position );

		if ( !empty( $ids ) ) {
			$this->position = end( $ids );
			reset( $ids );
		}

		return $ids;
	}
}
