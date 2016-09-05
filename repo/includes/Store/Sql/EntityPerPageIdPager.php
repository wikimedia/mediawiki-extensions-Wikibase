<?php

namespace Wikibase\Repo\Store\Sql;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Repo\Store\EntityIdPager;
use Wikibase\Lib\EntityPerPage;

/**
 * EntityPerPageIdPager is a cursor for iterating over batches of EntityIds from an
 * EntityPerPage service.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EntityPerPageIdPager implements EntityIdPager {

	/**
	 * @var EntityPerPage
	 */
	private $entityPerPage;

	/**
	 * @var string|null
	 */
	private $entityType;

	/**
	 * @var EntityId|null
	 */
	private $position = null;

	/**
	 * @var string
	 */
	private $redirectMode;

	/**
	 * @param EntityPerPage $entityPerPage
	 * @param null|string $entityType The desired entity type, or null for any type.
	 * @param string $redirectMode A EntityPerPage::XXX_REDIRECTS constant (default is NO_REDIRECTS).
	 */
	public function __construct( EntityPerPage $entityPerPage, $entityType = null, $redirectMode = EntityPerPage::NO_REDIRECTS ) {
		$this->entityPerPage = $entityPerPage;
		$this->entityType = $entityType;
		$this->redirectMode = $redirectMode;
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
		$ids = $this->entityPerPage->listEntities( $this->entityType, $limit, $this->position, $this->redirectMode );

		if ( !empty( $ids ) ) {
			$this->position = end( $ids );
			reset( $ids );
		}

		return $ids;
	}

}
